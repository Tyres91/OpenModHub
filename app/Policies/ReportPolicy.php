<?php

namespace App\Policies;

use App\Models\Mod;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $_user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('handle_reports');
    }

    public function review(User $user, Report $_report): bool
    {
        return $user->hasPermission('handle_reports');
    }
}
