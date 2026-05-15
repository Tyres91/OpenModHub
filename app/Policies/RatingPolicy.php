<?php

namespace App\Policies;

use App\Models\Mod;
use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    public function create(User $user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function update(User $user, Rating $rating): bool
    {
        return $rating->user_id === $user->id && $rating->mod->isApproved();
    }
}
