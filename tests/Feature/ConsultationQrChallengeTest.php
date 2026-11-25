<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\ConsultationQrChallenge;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationQrChallengeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A rider can generate a QR challenge after mechanic acceptance.
     */
    public function test_rider_can_generate_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $response->assertCreated()
            ->assertJsonPath('message', 'QR challenge generated successfully.')
            ->assertJsonPath('data.expires_in_seconds', 60);

        $token = $response->json('data.token');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        // Database must contain the hash, never the raw token.
        $this->assertSame(
            hash('sha256', $token),
            $challenge->token_hash
        );

        $this->assertNotSame($token, $challenge->token_hash);

        // QR challenge should expire approximately 60 seconds after creation.
        $this->assertTrue(
            $challenge->expires_at->between(
                now()->subSeconds(1),
                now()->addSeconds(60)
            )
        );
    }

    /**
     * A rider cannot generate a QR for another rider's consultation.
     */
    public function test_another_rider_cannot_generate_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $anotherRider = User::factory()->create();

        $this->actingAs($anotherRider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge")
            ->assertForbidden();

        $this->assertDatabaseCount('consultation_qr_challenges', 0);
    }

    /**
     * A pending consultation cannot generate a QR challenge.
     */
    public function test_pending_consultation_cannot_generate_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(false);

        $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge")
            ->assertUnprocessable();

        $this->assertDatabaseCount('consultation_qr_challenges', 0);
    }

    /**
     * Generating a new QR revokes the previous challenge.
     */
    public function test_generating_new_qr_revokes_previous_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $firstResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $firstResponse->assertCreated();

        $firstToken = $firstResponse->json('data.token');

        $firstChallenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        $secondResponse = $this->postJson(
            "/api/consultations/{$consultation->id}/qr-challenge"
        );

        $secondResponse->assertCreated();

        $secondToken = $secondResponse->json('data.token');

        $firstChallenge->refresh();

        $secondChallenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )
            ->whereNull('revoked_at')
            ->firstOrFail();

        $this->assertNotSame($firstToken, $secondToken);

        $this->assertNotNull($firstChallenge->revoked_at);
        $this->assertNull($secondChallenge->revoked_at);

        $this->assertDatabaseCount('consultation_qr_challenges', 2);
    }


    /**
     * The assigned mechanic can scan a valid QR challenge.
     */
    public function test_assigned_mechanic_can_scan_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $token = $qrResponse->json('data.token');
        $mechanicUser = $consultation->mechanic->user;

        $response = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $token]
            );

        $response->assertOk();

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        $this->assertNotNull($challenge->scanned_at);
        $this->assertEquals(
            $mechanicUser->id,
            $challenge->scanned_by_user_id
        );

        // Scanning alone must not start the consultation.
        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }

    /**
     * An unassigned mechanic cannot scan another mechanic's QR.
     */
    public function test_unassigned_mechanic_cannot_scan_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $token = $qrResponse->json('data.token');

        $anotherMechanic = Mechanic::factory()
            ->verified()
            ->available()
            ->create();

        $this->actingAs($anotherMechanic->user, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $token]
            )
            ->assertForbidden();

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        $this->assertNull($challenge->scanned_at);
        $this->assertNull($challenge->scanned_by_user_id);
    }

    /**
     * An invalid QR token is rejected.
     */
    public function test_invalid_qr_token_is_rejected(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $mechanicUser = $consultation->mechanic->user;

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => str_repeat('a', 64)]
            )
            ->assertUnprocessable();

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        $this->assertNull($challenge->scanned_at);
    }

    /**
     * An expired QR challenge cannot be scanned.
     */
    public function test_expired_qr_challenge_cannot_be_scanned(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        $challenge->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $mechanicUser = $consultation->mechanic->user;

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            )
            ->assertUnprocessable();

        $challenge->refresh();

        $this->assertNull($challenge->scanned_at);
    }

    /**
     * Rider can detect when the assigned mechanic scans the QR.
     */
    public function test_rider_can_view_pending_mechanic_scan(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $mechanic = $consultation->mechanic;
        $mechanicUser = $mechanic->user;

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            )
            ->assertOk();

        $response = $this->actingAs($rider, 'sanctum')
            ->getJson("/api/consultations/{$consultation->id}/qr-challenge/pending");

        $response->assertOk()
            ->assertJsonPath('data.consultation_id', $consultation->id)
            ->assertJsonPath('data.mechanic.name', $mechanic->name)
            ->assertJsonPath('data.mechanic.shop_name', $mechanic->shop_name);
    }

    /**
     * Another rider cannot view a pending mechanic scan.
     */
    public function test_another_rider_cannot_view_pending_mechanic_scan(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $mechanicUser = $consultation->mechanic->user;

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            )
            ->assertOk();

        $anotherRider = User::factory()->create();

        $this->actingAs($anotherRider, 'sanctum')
            ->getJson("/api/consultations/{$consultation->id}/qr-challenge/pending")
            ->assertForbidden();
    }

    /**
     * The mechanic cannot access the rider's pending scan endpoint.
     */
    public function test_mechanic_cannot_view_rider_pending_scan(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $mechanicUser = $consultation->mechanic->user;

        $this->actingAs($mechanicUser, 'sanctum')
            ->getJson("/api/consultations/{$consultation->id}/qr-challenge/pending")
            ->assertForbidden();
    }

    /**
     * Rider confirmation starts the consultation.
     */
    public function test_rider_can_confirm_mechanic_arrival(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $mechanicUser = $consultation->mechanic->user;

        $scanResponse = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            );

        $scanResponse->assertOk();

        $challengeId = $scanResponse->json('data.challenge_id');

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/confirm",
                [
                    'challenge_id' => $challengeId,
                    'confirmed' => true,
                ]
            );

        $response->assertOk()
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath('data.status', 'ongoing');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
        ]);

        $this->assertNotNull($consultation->fresh()->arrived_at);

        $this->assertNotNull(
            ConsultationQrChallenge::findOrFail($challengeId)->confirmed_at
        );
    }

    /**
     * Rider rejection revokes the challenge without starting the consultation.
     */
    public function test_rider_can_reject_mechanic_arrival(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $mechanicUser = $consultation->mechanic->user;

        $scanResponse = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            );

        $scanResponse->assertOk();

        $challengeId = $scanResponse->json('data.challenge_id');

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/confirm",
                [
                    'challenge_id' => $challengeId,
                    'confirmed' => false,
                ]
            );

        $response->assertOk()
            ->assertJsonPath('data.confirmed', false)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);

        $this->assertNotNull(
            ConsultationQrChallenge::findOrFail($challengeId)->revoked_at
        );
    }

    /**
     * Another rider cannot confirm someone else's mechanic arrival.
     */
    public function test_another_rider_cannot_confirm_mechanic_arrival(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $mechanicUser = $consultation->mechanic->user;

        $scanResponse = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            );

        $scanResponse->assertOk();

        $anotherRider = User::factory()->create();

        $this->actingAs($anotherRider, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/confirm",
                [
                    'challenge_id' => $scanResponse->json('data.challenge_id'),
                    'confirmed' => true,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);
    }

    /**
     * An expired rider confirmation window cannot start the consultation.
     */
    public function test_expired_arrival_confirmation_cannot_be_confirmed(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $mechanicUser = $consultation->mechanic->user;

        $scanResponse = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            );

        $scanResponse->assertOk();

        $challengeId = $scanResponse->json('data.challenge_id');

        ConsultationQrChallenge::findOrFail($challengeId)
            ->forceFill([
                'confirmation_expires_at' => now()->subMinute(),
            ])->save();

        $this->actingAs($rider, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/confirm",
                [
                    'challenge_id' => $challengeId,
                    'confirmed' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The confirmation request has expired. Please generate a new QR code.'
            );

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'accepted',
            'arrived_at' => null,
        ]);

        $this->assertNotNull(
            ConsultationQrChallenge::findOrFail($challengeId)->revoked_at
        );
    }

    /**
     * A confirmed arrival cannot be confirmed a second time.
     */
    public function test_duplicate_arrival_confirmation_is_rejected(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $mechanicUser = $consultation->mechanic->user;

        $scanResponse = $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $qrResponse->json('data.token')]
            );

        $scanResponse->assertOk();

        $challengeId = $scanResponse->json('data.challenge_id');

        $payload = [
            'challenge_id' => $challengeId,
            'confirmed' => true,
        ];

        $this->actingAs($rider, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/confirm",
                $payload
            )
            ->assertOk();

        $this->postJson(
            "/api/consultations/{$consultation->id}/qr-challenge/confirm",
            $payload
        )->assertUnprocessable();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
        ]);
    }

    /**
     * An inactive assigned mechanic cannot scan a QR challenge.
     */
    public function test_inactive_assigned_mechanic_cannot_scan_qr_challenge(): void
    {
        [$rider, $consultation] = $this->createConsultation(true);

        $qrResponse = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/consultations/{$consultation->id}/qr-challenge");

        $qrResponse->assertCreated();

        $token = $qrResponse->json('data.token');
        $mechanicUser = $consultation->mechanic->user;

        // Deactivate the assigned mechanic's account.
        $mechanicUser->forceFill(['is_active' => false])->save();
        $mechanicUser->refresh();

        $this->assertFalse($mechanicUser->is_active);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson(
                "/api/consultations/{$consultation->id}/qr-challenge/scan",
                ['token' => $token]
            )
            ->assertForbidden();

        $challenge = ConsultationQrChallenge::where(
            'consultation_id',
            $consultation->id
        )->firstOrFail();

        // The inactive mechanic must not be recorded as having scanned.
        $this->assertNull($challenge->scanned_at);
        $this->assertNull($challenge->scanned_by_user_id);
    }

    /**
     * Create a consultation and optionally simulate mechanic acceptance.
     */
    private function createConsultation(bool $accepted): array
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

        $consultation = Consultation::findOrFail(
            $response->json('data.id')
        );

        if ($accepted) {
            $consultation->forceFill([
                'status' => 'accepted',
            ])->save();
        }

        return [$rider, $consultation];
    }
}
