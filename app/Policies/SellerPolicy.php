<?php

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SellerPolicy
{
    // One seller profile per account. Response::deny() carries a readable message to the app.
    public function create(User $user): Response
    {
        return $user->seller
            ? Response::deny('You already have a seller profile.')
            : Response::allow();
    }

    public function update(User $user, Seller $seller): bool
    {
        return $seller->user_id === $user->id;
    }

    public function delete(User $user, Seller $seller): bool
    {
        return $seller->user_id === $user->id || $user->isAdmin();
    }

    // Admin only. The route is already inside the admin group; this is the second lock.
    public function verify(User $user, Seller $seller): bool
    {
        return $user->isAdmin();
    }
}