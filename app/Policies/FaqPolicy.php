<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->hasRole('admin');
    }
}
