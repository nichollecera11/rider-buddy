<?php

namespace Tests\Feature;

use App\Models\Mechanic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MechanicLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mechanic_can_be_created_with_valid_coordinates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mechanics', [
                'shop_name' => 'Rider Buddy Workshop',
                'address' => 'Cagayan de Oro City',
                'contact_number' => '09123456789',
                'years_experience' => 5,
                'diagnostic_fee_base' => 150,
                'latitude' => 8.4542,
                'longitude' => 124.6319,
            ])
            ->assertCreated()
            ->assertJsonPath('data.latitude', 8.4542)
            ->assertJsonPath('data.longitude', 124.6319);
    }

    public function test_mechanic_creation_rejects_latitude_without_longitude(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mechanics', [
                'address' => 'Cagayan de Oro City',
                'contact_number' => '09123456789',
                'years_experience' => 5,
                'diagnostic_fee_base' => 150,
                'latitude' => 8.4542,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('longitude');
    }

    public function test_mechanic_creation_rejects_longitude_without_latitude(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mechanics', [
                'address' => 'Cagayan de Oro City',
                'contact_number' => '09123456789',
                'years_experience' => 5,
                'diagnostic_fee_base' => 150,
                'longitude' => 124.6319,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');
    }

    public function test_mechanic_creation_rejects_coordinates_outside_valid_ranges(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mechanics', [
                'address' => 'Cagayan de Oro City',
                'contact_number' => '09123456789',
                'years_experience' => 5,
                'diagnostic_fee_base' => 150,
                'latitude' => 100,
                'longitude' => 200,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_mechanic_can_update_both_coordinates(): void
    {
        $mechanic = Mechanic::factory()->create([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->patchJson("/api/mechanics/{$mechanic->id}", [
                'latitude' => 8.4850,
                'longitude' => 124.6450,
            ])
            ->assertOk()
            ->assertJsonPath('data.latitude', 8.4850)
            ->assertJsonPath('data.longitude', 124.6450);
    }

    public function test_mechanic_update_rejects_latitude_without_longitude(): void
    {
        $mechanic = Mechanic::factory()->create();

        $this->actingAs($mechanic->user, 'sanctum')
            ->patchJson("/api/mechanics/{$mechanic->id}", [
                'latitude' => 8.4542,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('longitude');
    }

    public function test_mechanic_update_rejects_longitude_without_latitude(): void
    {
        $mechanic = Mechanic::factory()->create();

        $this->actingAs($mechanic->user, 'sanctum')
            ->patchJson("/api/mechanics/{$mechanic->id}", [
                'longitude' => 124.6319,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');
    }


    public function test_nearby_mechanics_are_returned_closest_first(): void
    {
        $near = \App\Models\Mechanic::factory()->create([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ]);

        $far = \App\Models\Mechanic::factory()->create([
            'latitude' => 8.5000,
            'longitude' => 124.6800,
        ]);

        $response = $this->getJson(
            '/api/mechanics?lat=8.4542&lng=124.6319'
        );


        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');

        $this->assertSame(
            $near->id,
            $ids->first(),
            'The nearest mechanic should appear first.'
        );

        $this->assertTrue(
            $ids->contains($far->id),
            'The farther mechanic should appear within the search radius.'
        );
    }

    public function test_nearby_search_excludes_mechanics_without_coordinates(): void
    {
        $withoutLocation = \App\Models\Mechanic::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson(
            '/api/mechanics?lat=8.4542&lng=124.6319'
        );

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');

        $this->assertFalse(
            $ids->contains($withoutLocation->id),
            'A mechanic without coordinates should not appear in nearby results.'
        );
    }

    public function test_nearby_search_rejects_invalid_coordinates(): void
    {
        $this->getJson('/api/mechanics?lat=100&lng=200')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_nearby_search_accepts_zero_coordinates(): void
    {
        $this->getJson('/api/mechanics?lat=0&lng=0')
            ->assertOk();
    }


    public function test_nearby_search_excludes_mechanics_outside_radius_at_bounding_box_corner(): void
    {
        $near = \App\Models\Mechanic::factory()->create([
            'latitude' => 8.4542,
            'longitude' => 124.6319,
        ]);

        // This point is within the rough bounding box but outside
        // the 50 km circular radius.
        $outside = \App\Models\Mechanic::factory()->create([
            'latitude' => 8.77,
            'longitude' => 124.95,
        ]);

        $response = $this->getJson(
            '/api/mechanics?lat=8.4542&lng=124.6319'
        );

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');

        $this->assertTrue($ids->contains($near->id));
        $this->assertFalse(
            $ids->contains($outside->id),
            'A mechanic outside the 50 km radius must not be returned.'
        );
    }

}
