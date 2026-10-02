<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserMotorcycle;
use Illuminate\Auth\Access\Response;

class UserMotorcyclePolicy
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
    public function view(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return $userMotorcycle->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return $userMotorcycle->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return $this->update($user, $userMotorcycle);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserMotorcycle $userMotorcycle): bool
    {
        return false;
    }
}
