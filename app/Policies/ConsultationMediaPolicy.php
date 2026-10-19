<?php

namespace App\Policies;

use App\Models\ConsultationMedia;
use App\Models\User;

class ConsultationMediaPolicy
{
    // Only the two people on the consultation may remove its media.
    public function delete(User $user, ConsultationMedia $media): bool
    {
        $consultation = $media->consultation;

        if (!$consultation) {
            return false;
        }

        // Only the rider who owns the consultation can remove photos, and only while it is still pending (see isLocked())
        return $media->consultation?->user_id === $user->id;
    }
}