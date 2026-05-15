<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function manageSettings(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
