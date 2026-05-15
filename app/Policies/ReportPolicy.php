<?php

namespace App\Policies;

use App\Models\Mod;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }

    public function review(User $user, Report $report): bool
    {
        return $user->hasRole('admin') || $user->hasRole('editor');
    }
}
