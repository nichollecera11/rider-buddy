<?php

namespace App\Policies;

use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MaintenanceLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $maintenanceLog->motorcycle->user_id === $user->id || ($user->mechanic && $maintenanceLog->mechanic_id === $user->mechanic->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->mechanic !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $user->mechanic && $maintenanceLog->mechanic_id === $user->mechanic->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return false;
    }
}
