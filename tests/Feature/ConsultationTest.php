<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ->assertJsonValidationErrors('verification_otp_input');

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
            ->assertJsonValidationErrors('verification_otp_input');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultationId,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
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
    public function test_rider_cannot_cancel_accepted_consultation(): void
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

        $response = $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/consultations/{$consultation->id}");

        $response->assertStatus(409);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'deleted_at' => null,
        ]);
    }

}
