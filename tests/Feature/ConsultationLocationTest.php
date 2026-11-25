<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ConsultationQrChallenge;

class ConsultationLocationTest extends TestCase
{
    use RefreshDatabase;

    private function createConsultation(string $status = 'pending'): array
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
            'status' => $status,
        ]);

        return [$rider, $mechanic, $consultation];
    }

    /**
     * A rider can update the breakdown location while pending.
     */
    public function test_rider_can_update_pending_consultation_location(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation();

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4542,
                'longitude' => 124.6319,
                'location_name' => 'Updated safe location',
            ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Breakdown location updated successfully.'
            );

        $consultation->refresh();

        $this->assertEqualsWithDelta(
            8.4542,
            (float) $consultation->latitude,
            0.00000001
        );

        $this->assertEqualsWithDelta(
            124.6319,
            (float) $consultation->longitude,
            0.00000001
        );

        $this->assertSame(
            'Updated safe location',
            $consultation->location_name
        );

        $this->assertSame('pending', $consultation->status);
    }

    /**
     * A rider can update the breakdown location while accepted.
     */
    public function test_rider_can_update_accepted_consultation_location(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
                'location_name' => 'New meeting point',
            ]);

        $response->assertOk();

        $consultation->refresh();

        $this->assertEqualsWithDelta(
            8.4600,
            (float) $consultation->latitude,
            0.00000001
        );

        $this->assertEqualsWithDelta(
            124.6400,
            (float) $consultation->longitude,
            0.00000001
        );

        $this->assertSame('accepted', $consultation->status);
    }
    /**
     * Updating coordinates without a location name preserves the existing name.
     */
    public function test_location_update_without_location_name_preserves_existing_name(): void
    {
        [$rider, , $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'location_name' => 'Limketkai Mall',
        ])->save();

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
            ]);

        $response->assertOk();

        $this->assertSame(
            'Limketkai Mall',
            $consultation->fresh()->location_name
        );
    }
    /**
     * Another rider cannot update someone else's consultation location.
     */
    public function test_another_rider_cannot_update_consultation_location(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation();

        $anotherRider = User::factory()->create();

        $originalLatitude = (float) $consultation->latitude;
        $originalLongitude = (float) $consultation->longitude;

        $response = $this->actingAs($anotherRider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
                'location_name' => 'Unauthorized location',
            ]);

        $response->assertForbidden();

        $consultation->refresh();

        $this->assertEqualsWithDelta(
            $originalLatitude,
            (float) $consultation->latitude,
            0.00000001
        );

        $this->assertEqualsWithDelta(
            $originalLongitude,
            (float) $consultation->longitude,
            0.00000001
        );
    }
    /**
     * The assigned mechanic cannot update the rider-reported location.
     */
    public function test_assigned_mechanic_cannot_update_consultation_location(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation();

        $originalLatitude = (float) $consultation->latitude;
        $originalLongitude = (float) $consultation->longitude;

        $response = $this->actingAs($mechanic->user, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
                'location_name' => 'Mechanic submitted location',
            ]);

        $response->assertForbidden();

        $consultation->refresh();

        $this->assertEqualsWithDelta(
            $originalLatitude,
            (float) $consultation->latitude,
            0.00000001
        );

        $this->assertEqualsWithDelta(
            $originalLongitude,
            (float) $consultation->longitude,
            0.00000001
        );
    }
    /**
     * A rider cannot update the location once service has started.
     */
    public function test_rider_cannot_update_ongoing_consultation_location(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('ongoing');

        $originalLatitude = (float) $consultation->latitude;
        $originalLongitude = (float) $consultation->longitude;

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
                'location_name' => 'Late location change',
            ]);

        $response->assertForbidden();

        $consultation->refresh();

        $this->assertEqualsWithDelta(
            $originalLatitude,
            (float) $consultation->latitude,
            0.00000001
        );

        $this->assertEqualsWithDelta(
            $originalLongitude,
            (float) $consultation->longitude,
            0.00000001
        );
    }
    /**
     * A rider cannot update the location of completed or cancelled consultations.
     */
    public function test_rider_cannot_update_finalized_consultation_location(): void
    {
        foreach (['completed', 'cancelled'] as $status) {
            [$rider, $mechanic, $consultation] = $this->createConsultation($status);

            $originalLatitude = (float) $consultation->latitude;
            $originalLongitude = (float) $consultation->longitude;

            $response = $this->actingAs($rider, 'sanctum')
                ->patchJson("/api/consultations/{$consultation->id}/location", [
                    'latitude' => 8.4600,
                    'longitude' => 124.6400,
                    'location_name' => 'Late location change',
                ]);

            $response->assertForbidden();

            $consultation->refresh();

            $this->assertEqualsWithDelta(
                $originalLatitude,
                (float) $consultation->latitude,
                0.00000001
            );

            $this->assertEqualsWithDelta(
                $originalLongitude,
                (float) $consultation->longitude,
                0.00000001
            );
        }
    }
    /**
     * Invalid GPS coordinates are rejected.
     */
    public function test_invalid_coordinates_are_rejected(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation();

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson("/api/consultations/{$consultation->id}/location", [
                'latitude' => 95,
                'longitude' => 200,
                'location_name' => 'Invalid coordinates',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'latitude',
                'longitude',
            ]);
    }
    private function createActiveQrChallenge(
        Consultation $consultation,
        ?int $mechanicUserId = null,
        bool $scanned = false
    ): ConsultationQrChallenge {
        return ConsultationQrChallenge::create([
            'consultation_id' => $consultation->id,
            'token_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'expires_at' => now()->addSeconds(60),
            'scanned_by_user_id' => $scanned ? $mechanicUserId : null,
            'scanned_at' => $scanned ? now() : null,
            'confirmation_expires_at' => $scanned
                ? now()->addMinutes(2)
                : null,
        ]);
    }
    public function test_location_update_within_50_meters_keeps_active_qr_and_original_coordinates(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ])->save();

        $qr = $this->createActiveQrChallenge($consultation);

        $response = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4544,
                'longitude' => 124.6319,
                'location_name' => 'Updated nearby location',
            ]
        );

        $response->assertOk();

        $this->assertNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4542,
            (float) $consultation->fresh()->latitude
        );

        $this->assertEquals(
            124.6319,
            (float) $consultation->fresh()->longitude
        );

        $this->assertEquals(
            'Updated nearby location',
            $consultation->fresh()->location_name
        );
    }

    public function test_location_update_beyond_50_meters_revokes_active_qr(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ])->save();

        $qr = $this->createActiveQrChallenge($consultation);

        $response = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4550,
                'longitude' => 124.6319,
                'location_name' => 'New breakdown location',
            ]
        );

        $response->assertOk();

        $this->assertNotNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4550,
            (float) $consultation->fresh()->latitude
        );

        $this->assertEquals(
            124.6319,
            (float) $consultation->fresh()->longitude
        );
    }

    public function test_cumulative_location_movement_beyond_50_meters_revokes_active_qr(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ])->save();

        $qr = $this->createActiveQrChallenge($consultation);

        // First update: approximately 33 meters from the original location.
        $firstResponse = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4545,
                'longitude' => 124.6319,
            ]
        );

        $firstResponse->assertOk();

        // QR must remain active and original coordinates must remain unchanged.
        $this->assertNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4542,
            (float) $consultation->fresh()->latitude
        );

        // Second update: approximately 67 meters from the original location.
        $secondResponse = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4548,
                'longitude' => 124.6319,
            ]
        );

        $secondResponse->assertOk();

        // QR must now be revoked because cumulative movement exceeded 50 meters.
        $this->assertNotNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4548,
            (float) $consultation->fresh()->latitude
        );
    }

    public function test_location_update_beyond_50_meters_revokes_scanned_qr_awaiting_confirmation(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ])->save();

        $qr = $this->createActiveQrChallenge(
            $consultation,
            $mechanic->user_id,
            true
        );

        $response = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4550,
                'longitude' => 124.6319,
                'location_name' => 'Updated breakdown location',
            ]
        );

        $response->assertOk();

        $this->assertNotNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4550,
            (float) $consultation->fresh()->latitude
        );
    }

    public function test_expired_qr_does_not_interfere_with_location_update(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ])->save();

        $qr = ConsultationQrChallenge::create([
            'consultation_id' => $consultation->id,
            'token_hash' => hash('sha256', bin2hex(random_bytes(32))),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4550,
                'longitude' => 124.6319,
                'location_name' => 'New breakdown location',
            ]
        );

        $response->assertOk();

        // Expired QR should not be treated as an active challenge.
        $this->assertNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4550,
            (float) $consultation->fresh()->latitude
        );
    }

    public function test_location_update_without_original_coordinates_revokes_active_qr(): void
    {
        [$rider, $mechanic, $consultation] = $this->createConsultation('accepted');

        $consultation->forceFill([
            'latitude' => null,
            'longitude' => null,
        ])->save();

        $qr = $this->createActiveQrChallenge($consultation);

        $response = $this->actingAs($rider)->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4550,
                'longitude' => 124.6319,
                'location_name' => 'New breakdown location',
            ]
        );

        $response->assertOk();

        $this->assertNotNull($qr->fresh()->revoked_at);

        $this->assertEquals(
            8.4550,
            (float) $consultation->fresh()->latitude
        );

        $this->assertEquals(
            124.6319,
            (float) $consultation->fresh()->longitude
        );
    }
    /**
     * An unauthenticated user cannot update a consultation location.
     */
    public function test_unauthenticated_user_cannot_update_consultation_location(): void
    {
        [, , $consultation] = $this->createConsultation();

        $response = $this->patchJson(
            "/api/consultations/{$consultation->id}/location",
            [
                'latitude' => 8.4600,
                'longitude' => 124.6400,
                'location_name' => 'Unauthorized location',
            ]
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'pending',
        ]);
    }
    /**
     * A banned rider cannot update a consultation location.
     */
    public function test_banned_rider_cannot_update_consultation_location(): void
    {
        [$rider, , $consultation] = $this->createConsultation();

        $rider->forceFill([
            'is_banned' => true,
        ])->save();

        $response = $this->actingAs($rider, 'sanctum')
            ->patchJson(
                "/api/consultations/{$consultation->id}/location",
                [
                    'latitude' => 8.4600,
                    'longitude' => 124.6400,
                    'location_name' => 'Banned rider location update',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'pending',
        ]);
    }
}
