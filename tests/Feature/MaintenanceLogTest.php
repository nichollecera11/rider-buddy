<?php

namespace Tests\Feature;

use App\Models\MaintenanceLog;
use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class MaintenanceLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_logs_require_authentication(): void
    {
        $this->getJson('/api/maintenance-logs')
            ->assertUnauthorized();
    }
    public function test_rider_can_only_list_own_maintenance_logs(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $otherMotorcycle = UserMotorcycle::factory()->create([
            'user_id' => $anotherRider->id,
        ]);

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
        ]);

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $otherMotorcycle->id,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs')
            ->assertOk()
            ->assertJsonPath('count', 1);
    }

    public function test_rider_can_filter_maintenance_logs_by_motorcycle(): void
    {
        $rider = User::factory()->create();

        $motorcycleOne = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $motorcycleTwo = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $logOne = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycleOne->id,
            'service_type' => 'Oil Change',
        ]);

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycleTwo->id,
            'service_type' => 'Brake Inspection',
        ]);

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs?user_motorcycle_id=' . $motorcycleOne->id)
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $logOne->id)
            ->assertJsonPath('data.0.service_type', 'Oil Change');
    }
    public function test_rider_can_list_maintenance_logs_for_archived_motorcycle(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
            'service_type' => 'Oil Change',
        ]);

        $motorcycle->delete();

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $log->id);
    }

    public function test_rider_can_filter_maintenance_logs_by_archived_motorcycle(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
            'service_type' => 'Brake Inspection',
        ]);

        $motorcycle->delete();

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs?user_motorcycle_id=' . $motorcycle->id)
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $log->id)
            ->assertJsonPath('data.0.service_type', 'Brake Inspection');
    }
    public function test_rider_cannot_filter_maintenance_logs_by_another_riders_motorcycle(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $otherMotorcycle = UserMotorcycle::factory()->create([
            'user_id' => $anotherRider->id,
        ]);

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $otherMotorcycle->id,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs?user_motorcycle_id=' . $otherMotorcycle->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Motorcycle not found.');
    }

    public function test_rider_cannot_filter_maintenance_logs_using_nonexistent_motorcycle(): void
    {
        $rider = User::factory()->create();

        $this->actingAs($rider, 'sanctum')
            ->getJson('/api/maintenance-logs?user_motorcycle_id=999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Motorcycle not found.');
    }
    public function test_mechanic_can_only_list_assigned_maintenance_logs(): void
    {
        $mechanic = Mechanic::factory()->create();
        $anotherMechanic = Mechanic::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create();

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
        ]);

        MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $anotherMechanic->id,
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->getJson('/api/maintenance-logs')
            ->assertOk()
            ->assertJsonPath('count', 1);
    }
    public function test_rider_can_view_own_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->getJson("/api/maintenance-logs/{$log->id}")
            ->assertOk();
    }
    public function test_rider_cannot_view_another_riders_maintenance_log(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $anotherRider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->getJson("/api/maintenance-logs/{$log->id}")
            ->assertForbidden();
    }
    public function test_assigned_mechanic_can_view_maintenance_log(): void
    {
        $mechanic = Mechanic::factory()->create();

        $log = MaintenanceLog::factory()->create([
            'mechanic_id' => $mechanic->id,
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->getJson("/api/maintenance-logs/{$log->id}")
            ->assertOk();
    }
    public function test_unassigned_mechanic_cannot_view_maintenance_log(): void
    {
        $mechanic = Mechanic::factory()->create();
        $anotherMechanic = Mechanic::factory()->create();

        $log = MaintenanceLog::factory()->create([
            'mechanic_id' => $anotherMechanic->id,
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->getJson("/api/maintenance-logs/{$log->id}")
            ->assertForbidden();
    }
    public function test_admin_cannot_delete_maintenance_log_through_normal_api(): void
    {
        $admin = User::factory()->admin()->create();

        $log = MaintenanceLog::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/maintenance-logs/{$log->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('maintenance_logs', [
            'id' => $log->id,
            'deleted_at' => null,
        ]);
    }
    public function test_rider_cannot_delete_maintenance_log_through_normal_api(): void
    {
        $rider = User::factory()->create();

        $log = MaintenanceLog::factory()->create();

        $this->actingAs($rider, 'sanctum')
            ->deleteJson("/api/maintenance-logs/{$log->id}")
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('maintenance_logs', [
            'id' => $log->id,
            'deleted_at' => null,
        ]);
    }
    public function test_maintenance_logs_cannot_be_updated(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
            'description' => 'Original maintenance record',
        ]);

        $this->actingAs($rider, 'sanctum');

        $this->putJson("/api/maintenance-logs/{$log->id}", [
            'description' => 'Changed maintenance record',
        ])->assertMethodNotAllowed();

        $this->patchJson("/api/maintenance-logs/{$log->id}", [
            'description' => 'Changed maintenance record',
        ])->assertMethodNotAllowed();

        $this->assertDatabaseHas('maintenance_logs', [
            'id' => $log->id,
            'description' => 'Original maintenance record',
        ]);
    }
    public function test_assigned_verified_mechanic_can_create_maintenance_log_without_completing_consultation(): void
    {
        $rider = User::factory()->create();

        $mechanic = Mechanic::factory()->verified()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Changed engine oil',
                'odometer_reading' => 15000,
                'cost' => 450.50,
            ])
            ->assertCreated()
            ->assertJsonPath('log.consultation_id', $consultation->id)
            ->assertJsonPath('consultation.status', 'ongoing')
            ->assertJsonPath('consultation.payment_status', 'pending');

        $this->assertDatabaseHas('maintenance_logs', [
            'consultation_id' => $consultation->id,
            'user_motorcycle_id' => $motorcycle->id,
            'mechanic_id' => $mechanic->id,
            'service_type' => 'Oil Change',
        ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }
    public function test_consultation_can_have_multiple_maintenance_logs(): void
    {
        $rider = User::factory()->create();

        $mechanic = Mechanic::factory()->verified()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanic->user, 'sanctum');

        // First maintenance service
        $this->postJson('/api/maintenance-logs', [
            'consultation_id' => $consultation->id,
            'service_type' => 'Oil Change',
            'description' => 'Replaced engine oil',
            'odometer_reading' => 15000,
            'cost' => 450,
        ])->assertCreated();

        // Second maintenance service under the same consultation
        $this->postJson('/api/maintenance-logs', [
            'consultation_id' => $consultation->id,
            'service_type' => 'Spark Plug Replacement',
            'description' => 'Replaced worn spark plug',
            'odometer_reading' => 15000,
            'cost' => 250,
        ])->assertCreated();

        $this->assertDatabaseCount('maintenance_logs', 2);

        $this->assertDatabaseHas('maintenance_logs', [
            'consultation_id' => $consultation->id,
            'service_type' => 'Oil Change',
        ]);

        $this->assertDatabaseHas('maintenance_logs', [
            'consultation_id' => $consultation->id,
            'service_type' => 'Spark Plug Replacement',
        ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }
    public function test_unassigned_mechanic_cannot_create_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $assignedMechanic = Mechanic::factory()->verified()->create();
        $unassignedMechanic = Mechanic::factory()->verified()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $assignedMechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($unassignedMechanic->user, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Unauthorized maintenance attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);

        $this->assertDatabaseHas('consultations', [
            'id' => $consultation->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);
    }
    public function test_unverified_mechanic_cannot_create_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $mechanic = Mechanic::factory()->unverified()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanic->user, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Unverified mechanic attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);
    }
    public function test_inactive_mechanic_cannot_create_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $mechanicUser = User::factory()
            ->mechanic()
            ->inactive()
            ->create();

        $mechanic = Mechanic::factory()
            ->verified()
            ->create([
                'user_id' => $mechanicUser->id,
            ]);

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Inactive mechanic attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);
    }
    public function test_banned_mechanic_cannot_create_maintenance_log(): void
    {
        $rider = User::factory()->create();

        $mechanicUser = User::factory()
            ->mechanic()
            ->banned()
            ->create();

        $mechanic = Mechanic::factory()
            ->verified()
            ->create([
                'user_id' => $mechanicUser->id,
            ]);

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Banned mechanic attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);
    }
    public function test_mechanic_cannot_create_maintenance_log_for_non_ongoing_consultation(): void
    {
        $rider = User::factory()->create();

        $mechanicUser = User::factory()->mechanic()->create();

        $mechanic = Mechanic::factory()
            ->verified()
            ->create([
                'user_id' => $mechanicUser->id,
            ]);

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'accepted',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Premature maintenance attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);
    }
    public function test_mechanic_cannot_create_maintenance_log_when_motorcycle_belongs_to_another_rider(): void
    {
        $rider = User::factory()->create();
        $anotherRider = User::factory()->create();

        $mechanicUser = User::factory()->mechanic()->create();

        $mechanic = Mechanic::factory()
            ->verified()
            ->create([
                'user_id' => $mechanicUser->id,
            ]);

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $anotherRider->id,
        ]);

        $consultation = Consultation::factory()->create([
            'user_id' => $rider->id,
            'mechanic_id' => $mechanic->id,
            'user_motorcycle_id' => $motorcycle->id,
            'status' => 'ongoing',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'consultation_id' => $consultation->id,
                'service_type' => 'Oil Change',
                'description' => 'Motorcycle ownership mismatch attempt',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('maintenance_logs', [
            'consultation_id' => $consultation->id,
        ]);
    }
    public function test_maintenance_log_requires_consultation_id(): void
    {
        $mechanicUser = User::factory()->mechanic()->create();

        Mechanic::factory()
            ->verified()
            ->create([
                'user_id' => $mechanicUser->id,
            ]);

        $this->actingAs($mechanicUser, 'sanctum')
            ->postJson('/api/maintenance-logs', [
                'service_type' => 'Oil Change',
                'description' => 'Missing consultation ID',
                'odometer_reading' => 15000,
                'cost' => 450,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('maintenance_logs', 0);
    }
    public function test_rider_can_view_maintenance_log_after_motorcycle_is_soft_deleted(): void
    {
        $rider = User::factory()->create();

        $motorcycle = UserMotorcycle::factory()->create([
            'user_id' => $rider->id,
        ]);

        $log = MaintenanceLog::factory()->create([
            'user_motorcycle_id' => $motorcycle->id,
        ]);

        $motorcycle->delete();

        $this->actingAs($rider, 'sanctum')
            ->getJson("/api/maintenance-logs/{$log->id}")
            ->assertOk();
    }
}
