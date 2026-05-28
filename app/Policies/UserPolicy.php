<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_users') || $user->hasPermission('moderate_users');
    }

    public function update(User $user, User $_target): bool
    {
        return $user->hasPermission('manage_users');
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        if ($target->hasRole('admin')) {
            $otherAdmins = User::query()
                ->where('id', '!=', $target->id)
                ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
                ->exists();

            if (! $otherAdmins) {
                return false;
            }
        }

        return true;
    }

    public function warn(User $user): bool
    {
        return $user->hasPermission('moderate_users');
    }

    public function sanction(User $user): bool
    {
        return $user->hasPermission('moderate_users');
    }
}
