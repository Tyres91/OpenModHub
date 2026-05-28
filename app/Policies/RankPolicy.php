<?php

namespace App\Policies;

use App\Models\Rank;
use App\Models\User;

class RankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_ranks');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_ranks');
    }

    public function update(User $user, Rank $_rank): bool
    {
        return $user->hasPermission('manage_ranks');
    }

    public function delete(User $user, Rank $_rank): bool
    {
        return $user->hasPermission('manage_ranks');
    }
}
