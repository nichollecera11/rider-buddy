<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMotorcycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A rider cannot delete a motorcycle with an unresolved consultation.
     */
    public function test_rider_cannot_delete_motorcycle_with_pending_consultation(): void
    {
        $this->assertMotorcycleDeletionBlocked('pending');
    }

    public function test_rider_cannot_delete_motorcycle_with_accepted_consultation(): void
    {
        $this->assertMotorcycleDeletionBlocked('accepted');
    }

    public function test_rider_cannot_delete_motorcycle_with_ongoing_consultation(): void
    {
        $this->assertMotorcycleDeletionBlocked('ongoing');
    }

    /**
     * A rider can delete a motorcycle when it has no unresolved consultation.
     */
    public function test_rider_can_delete_motorcycle_without_unresolved_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/user-motorcycles/{$motorcycle->id}")
            ->assertOk();

        $this->assertSoftDeleted('user_motorcycles', [
            'id' => $motorcycle->id,
        ]);
    }

    /**
     * A completed consultation does not prevent motorcycle deletion.
     */
    public function test_rider_can_delete_motorcycle_with_completed_consultation(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'completed',
        ]);

        $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/user-motorcycles/{$motorcycle->id}")
            ->assertOk();

        $this->assertSoftDeleted('user_motorcycles', [
            'id' => $motorcycle->id,
        ]);
    }

    private function assertMotorcycleDeletionBlocked(string $status): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $mechanic = Mechanic::factory()->create();

        Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => $status,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/user-motorcycles/{$motorcycle->id}")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Motorcycle cannot be deleted while an unresolved consultation exists.'
            );

        $this->assertDatabaseHas('user_motorcycles', [
            'id' => $motorcycle->id,
            'deleted_at' => null,
        ]);
    }
}
