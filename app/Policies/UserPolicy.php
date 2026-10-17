<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // These routes are already limited to admins by the 'admin' middleware.
    // The Policy repeats that and adds the rules the middleware cannot express.

    public function update(User $admin, User $target): bool
    {
        return $admin->isAdmin();
    }

    // Used for both ban and unban. An admin cannot ban themselves.
    public function ban(User $admin, User $target): bool
    {
        return $admin->isAdmin() && !$admin->is($target);
    }

    // An admin cannot delete themselves.
    public function delete(User $admin, User $target): bool
    {
        return $admin->isAdmin() && !$admin->is($target);
    }
}