<?php

namespace App\Policies;

use App\Models\LTOCompliance;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\UserMotorcycle;

class LTOCompliancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LTOCompliance $lTOCompliance): bool
    {
        return $user->isAdmin() || $lTOCompliance->user_motorcycle->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return $userMotorcycle->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LTOCompliance $lTOCompliance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LTOCompliance $lTOCompliance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LTOCompliance $lTOCompliance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LTOCompliance $lTOCompliance): bool
    {
        return false;
    }

    public function verify(User $user, LTOCompliance $ltoCompliance): bool
    {
        return $user->isAdmin();
    }
}
