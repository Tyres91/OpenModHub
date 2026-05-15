<?php

namespace App\Policies;

use App\Models\Rank;
use App\Models\User;

class RankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Rank $rank): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Rank $rank): bool
    {
        return $user->hasRole('admin');
    }
}
