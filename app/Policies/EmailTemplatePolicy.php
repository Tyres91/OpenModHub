<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_settings');
    }

    public function update(User $user, EmailTemplate $_emailTemplate): bool
    {
        return $user->hasPermission('manage_settings');
    }
}
