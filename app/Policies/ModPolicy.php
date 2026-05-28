<?php

namespace App\Policies;

use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\Setting;
use App\Models\User;
use App\Services\WarningService;

class ModPolicy
{
    public function view(?User $user, Mod $mod): bool
    {
        if ($mod->isApproved()) {
            return true;
        }

        return $user !== null && ($mod->user_id === $user->id || $user->hasPermission('review_mods'));
    }

    public function create(User $user): bool
    {
        if ($user->isBlocked()) {
            return false;
        }

        if (app(WarningService::class)->isUploadBanned($user)) {
            return false;
        }

        if ($user->hasPermission('review_mods')) {
            return true;
        }

        if (Setting::get('mod_submissions_blocked', '0') === '1') {
            return false;
        }

        $limit = (int) Setting::get('mod_pending_submission_limit', 5);

        if ($limit === 0) {
            return true;
        }

        return $user->mods()
            ->where('status', Mod::STATUS_PENDING)
            ->count() < $limit;
    }

    public function reviewAny(User $user): bool
    {
        return $user->hasPermission('review_mods');
    }

    public function update(User $user, Mod $mod): bool
    {
        if ($user->hasPermission('edit_any_mod')) {
            return true;
        }

        return $mod->user_id === $user->id && in_array($mod->status, [Mod::STATUS_PENDING, Mod::STATUS_REJECTED], true);
    }

    public function delete(User $user, Mod $_mod): bool
    {
        return $user->hasPermission('delete_any_mod');
    }

    public function forceDelete(User $user, Mod $_mod): bool
    {
        return $user->hasPermission('delete_any_mod');
    }

    public function approve(User $user, Mod $_mod): bool
    {
        return $user->hasPermission('review_mods');
    }

    public function reject(User $user, Mod $_mod): bool
    {
        return $user->hasPermission('review_mods');
    }

    public function rate(User $_user, Mod $mod): bool
    {
        return $mod->isApproved();
    }

    public function submitVersion(User $user, Mod $mod): bool
    {
        return $mod->user_id === $user->id && $mod->isApproved();
    }

    public function viewVersion(?User $user, Mod $mod, ModVersion $modVersion): bool
    {
        if ($modVersion->mod_id !== $mod->id) {
            return false;
        }

        if ($modVersion->isApproved()) {
            return true;
        }

        return $user !== null && ($mod->user_id === $user->id || $user->hasPermission('review_mods'));
    }
}
