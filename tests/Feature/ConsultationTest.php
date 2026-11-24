<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\MaintenanceLog;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A rider can successfully create an SOS consultation
     * with an eligible mechanic and their own motorcycle.
     */
    public function test_rider_can_create_sos_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
                'latitude' => 8.4542,
                'longitude' => 124.6319,
                'location_name' => 'Cagayan de Oro City',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.consultation_type', 'sos');

        $this->assertDatabaseHas('consultations', [
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'status' => 'pending',
        ]);

        $otp = $response->json('data.verification_otp');

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            (string) $otp,
            'The rider should receive a six-digit verification OTP.'
        );
    }

    /**
     * An unverified mechanic cannot receive a new consultation.
     */
    public function test_consultation_rejects_unverified_mechanic(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->unverified()
            ->available()
            ->create();

        $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('consultations', [
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
        ]);
    }

    /**
     * A rider cannot create a consultation using another rider's motorcycle.
     */
    public function test_rider_cannot_use_another_riders_motorcycle(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $otherMotorcycle = UserMotorcycle::factory()->create([
            'user_id' => $anotherRider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $otherMotorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_motorcycle_id');

        $this->assertDatabaseCount('consultations', 0);
    }
    public function test_rider_cannot_create_a_second_unresolved_sos(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $payload = [
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'latitude' => 8.4542,
            'longitude' => 124.6319,
            'location_name' => 'Cagayan de Oro City',
        ];

        // First SOS should be created successfully.
        $firstResponse = $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', $payload);

        $firstResponse->assertCreated();

        $firstConsultationId = $firstResponse->json('data.id');

        // Second unresolved SOS should be rejected.
        $secondResponse = $this->postJson('/api/consultations', $payload);

        $secondResponse->assertStatus(409)
            ->assertJsonPath('data.id', $firstConsultationId);

        // Only one consultation should exist.
        $this->assertDatabaseCount('consultations', 1);

    }

    public function test_consultation_rejects_banned_mechanic(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        // Ban the mechanic's user account.
        $mechanic->user()->update([
            'is_banned' => true,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('consultations', 0);
    }
    public function test_consultation_rejects_inactive_mechanic(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $mechanic->user()->update([
            'is_active' => false,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('consultations', 0);
    }
    public function test_expired_otp_cannot_start_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        // Rider creates the SOS consultation.
        $response = $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ]);

        $response->assertCreated();

        $consultationId = $response->json('data.id');
        $otp = $response->json('data.verification_otp');

        // Simulate mechanic acceptance and an expired OTP.
        $consultation = Consultation::findOrFail($consultationId);

        $consultation->forceFill([
            'status' => 'accepted',
            'verification_otp_created_at' => now()->subMinutes(3),
        ])->save();

        // Mechanic attempts to start service using the correct but expired OTP.
        $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultationId}", [
                'status' => 'ongoing',
                'verification_otp_input' => $otp,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        // Consultation must remain accepted.
        $this->assertDatabaseHas('consultations', [
            'id' => $consultationId,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }

    /**
     * A correct, unexpired OTP cannot bypass QR arrival confirmation.
     */
    public function test_valid_otp_cannot_bypass_qr_arrival_confirmation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        // Rider creates the SOS consultation.
        $response = $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ]);

        $response->assertCreated();

        $consultationId = $response->json('data.id');
        $otp = $response->json('data.verification_otp');

        // Simulate mechanic acceptance with a valid, unexpired OTP.
        $consultation = Consultation::findOrFail($consultationId);

        $consultation->forceFill([
            'status' => 'accepted',
            'verification_otp_created_at' => now()->subSeconds(10),
        ])->save();

        // Even the correct OTP cannot bypass QR confirmation.
        $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultationId}", [
                'status' => 'ongoing',
                'verification_otp_input' => $otp,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        // Consultation must remain accepted.
        $this->assertDatabaseHas('consultations', [
            'id' => $consultationId,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }
    public function test_invalid_otp_cannot_start_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson('/api/consultations', [
                'mechanic_id' => $mechanic->id,
                'user_motorcycle_id' => $motorcycle->id,
                'consultation_type' => 'sos',
                'issue_description' => 'My motorcycle engine suddenly stopped.',
            ]);

        $response->assertCreated();

        $consultationId = $response->json('data.id');

        $consultation = Consultation::findOrFail($consultationId);

        $consultation->forceFill([
            'status' => 'accepted',
        ])->save();

        $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultationId}", [
                'status' => 'ongoing',
                'verification_otp_input' => '000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultationId,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }




    /**
     * A mechanic cannot modify a completed consultation or attach new media.
     */
    public function test_mechanic_cannot_modify_completed_consultation(): void
    {
        Storage::fake('public');

        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'completed',
            'mechanic_notes' => 'Original repair notes.',
            'suggested_parts' => ['Spark plug'],
            'estimated_repair_costs' => 500,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultation->id}", [
                'mechanic_notes' => 'Changed repair notes.',
                'estimated_repair_costs' => 1000,
                'images' => [
                    UploadedFile::fake()->create(
                        'repair.jpg',
                        100,
                        'image/jpeg'
                    ),
                ],
                'videos' => [
                    UploadedFile::fake()->create(
                        'repair.mp4',
                        100,
                        'video/mp4'
                    ),
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'completed',
            'mechanic_notes' => 'Original repair notes.',
            'estimated_repair_costs' => 500,
        ]);

        $this->assertDatabaseCount('consultations_media', 0);

        $this->assertSame(
            ['Spark plug'],
            $consultation->fresh()->suggested_parts
        );

        $this->assertSame([], Storage::disk('public')->allFiles('consultation/media'));
    }

    /**
     * A rider can cancel their own pending consultation.
     */
    public function test_rider_can_cancel_pending_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle suddenly stopped.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}");

        $response->assertOk();

        $this->assertSoftDeleted('consultations', [
            'id' => $consultation->id,
        ]);
    }

    /**
     * A rider cannot cancel an accepted consultation.
     */

    /**
     * A rider can cancel an accepted consultation with a reason.
     * The consultation record must be preserved.
     */
    public function test_rider_can_cancel_accepted_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle suddenly stopped.',
            'status' => 'accepted',
        ]);

        $reason = 'I no longer need roadside assistance.';

        $response = $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}", [
                'reason' => $reason,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', $reason);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'cancelled',
            'cancelled_by' => $rider->id,
            'cancellation_reason' => $reason,
            'deleted_at' => null,
        ]);
    }

    /**
     * A rider can cancel an ongoing consultation with a reason.
     * The consultation record must be preserved.
     */
    public function test_rider_can_cancel_ongoing_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle suddenly stopped.',
            'status' => 'ongoing',
        ]);

        $reason = 'I need to stop the service for now.';

        $response = $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}", [
                'reason' => $reason,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', $reason);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'cancelled',
            'cancelled_by' => $rider->id,
            'cancellation_reason' => $reason,
            'deleted_at' => null,
        ]);
    }

    /**
     * Accepted and ongoing cancellations require a valid reason.
     */
    public function test_rider_cannot_cancel_accepted_consultation_without_valid_reason(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle suddenly stopped.',
            'status' => 'accepted',
        ]);

        $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}", [
                'reason' => 'No',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'deleted_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Another rider cannot cancel someone else's consultation.
     */
    public function test_another_rider_cannot_cancel_consultation(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle suddenly stopped.',
            'status' => 'accepted',
        ]);

        $this->actingAs($anotherRider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}", [
                'reason' => 'I want to cancel this request.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'cancelled_at' => null,
            'deleted_at' => null,
        ]);
    }


    /**
     * An assigned mechanic can complete an ongoing consultation
     * when a verified maintenance log exists.
     */
    public function test_mechanic_can_complete_consultation_with_verified_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.payment_status', 'pending');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'completed',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * A mechanic cannot complete a consultation without
     * a verified maintenance log.
     */
    public function test_mechanic_cannot_complete_consultation_without_verified_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'A verified maintenance log is required before completing this consultation.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * An unassigned mechanic cannot complete another mechanic's consultation.
     */
    public function test_unassigned_mechanic_cannot_complete_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $assignedMechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $otherMechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $assignedMechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($otherMechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'mechanic_id' => $assignedMechanic->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * An unverified mechanic cannot complete a consultation.
     */
    public function test_unverified_mechanic_cannot_complete_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->unverified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * An inactive mechanic cannot complete a consultation.
     */
    public function test_inactive_mechanic_cannot_complete_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $mechanic->user()->update([
            'is_active' => false,
        ]);

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * A banned mechanic cannot complete a consultation.
     */
    public function test_banned_mechanic_cannot_complete_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $mechanic->user()->update([
            'is_banned' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * An accepted consultation cannot be completed before service starts.
     */
    public function test_mechanic_cannot_complete_accepted_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'accepted',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Only ongoing consultations can be completed.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * A completed consultation cannot be completed again.
     */
    public function test_mechanic_cannot_complete_consultation_twice(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'completed',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'This consultation has already been completed.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'completed',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * A consultation cannot be completed if its motorcycle
     * no longer belongs to the consultation rider.
     */
    public function test_mechanic_cannot_complete_consultation_with_wrong_motorcycle_owner(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        // Simulate an ownership inconsistency.
        $motorcycle->update([
            'user_id' => $anotherRider->id,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'The consultation motorcycle does not belong to the rider.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }


    /**
     * An unverified maintenance log cannot satisfy
     * the consultation completion requirement.
     */
    public function test_mechanic_cannot_complete_consultation_with_unverified_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => false,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'A verified maintenance log is required before completing this consultation.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * A soft-deleted maintenance log cannot authorize consultation completion.
     */
    public function test_mechanic_cannot_complete_consultation_with_only_soft_deleted_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'consultation_type' => 'sos',
            'issue_description' => 'My motorcycle engine suddenly stopped.',
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $maintenanceLog = MaintenanceLog::factory()->create([
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'is_verified_by_mechanic' => true,
        ]);

        $maintenanceLog->delete();

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/complete");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'A verified maintenance log is required before completing this consultation.',
            ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }

    public function test_inactive_mechanic_cannot_accept_pending_consultation(): void
    {
        $rider = User::factory()->create();
        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $mechanic->user()->update([
            'is_active' => false,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultation->id}", [
                'status' => 'accepted',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'pending',
        ]);
    }

    public function test_banned_mechanic_cannot_accept_pending_consultation(): void
    {
        $rider = User::factory()->create();
        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $mechanic->user()->update([
            'is_banned' => true,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultation->id}", [
                'status' => 'accepted',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'pending',
        ]);
    }
    public function test_inactive_mechanic_cannot_start_accepted_consultation(): void
    {
        $rider = User::factory()->create();
        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'accepted',
            'verification_otp' => '123456',
            'verification_otp_created_at' => now(),
        ]);

        $mechanic->user()->update([
            'is_active' => false,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultation->id}", [
                'status' => 'ongoing',
                'verification_otp' => '123456',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }
    public function test_banned_mechanic_cannot_start_accepted_consultation(): void
    {
        $rider = User::factory()->create();
        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'accepted',
            'verification_otp' => '123456',
            'verification_otp_created_at' => now(),
        ]);

        $mechanic->user()->update([
            'is_banned' => true,
        ]);

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->putJson("/api/consultations/{$consultation->id}", [
                'status' => 'ongoing',
                'verification_otp' => '123456',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }
}
