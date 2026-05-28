<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserSanction;
use App\Models\Warning;
use Illuminate\Support\Carbon;

class WarningService
{
    public function issueWarning(User $user, int $points, string $reason, User $issuer, ?Carbon $expiresAt = null): Warning
    {
        if ($expiresAt === null) {
            $expiryDays = (int) Setting::get('warning_expiry_days', 90);
            $expiresAt = now()->addDays($expiryDays);
        }

        $warning = Warning::create([
            'user_id' => $user->id,
            'points' => $points,
            'reason' => $reason,
            'issued_by' => $issuer->id,
            'status' => Warning::STATUS_ACTIVE,
            'expires_at' => $expiresAt,
        ]);

        $this->evaluateSanctions($user, $issuer);

        return $warning;
    }

    public function removeWarning(Warning $warning, User $remover): void
    {
        $warning->update([
            'status' => Warning::STATUS_REMOVED,
            'removed_by' => $remover->id,
            'removed_at' => now(),
        ]);
    }

    public function getActivePoints(User $user): int
    {
        return (int) $user->warnings()
            ->active()
            ->sum('points');
    }

    public function evaluateSanctions(User $user, User $issuer): void
    {
        $activePoints = $this->getActivePoints($user);

        $uploadBanThreshold = (int) Setting::get('sanction_upload_ban_threshold', 5);
        $accountLockThreshold = (int) Setting::get('sanction_account_lock_threshold', 10);

        if ($activePoints >= $accountLockThreshold) {
            $this->applyAccountLock($user, $issuer, $activePoints);
        } elseif ($activePoints >= $uploadBanThreshold) {
            $this->applyUploadBan($user, $issuer, $activePoints);
        }
    }

    public function getActiveSanctions(User $user): array
    {
        return $user->sanctions()
            ->active()
            ->with(['issuer:id,name'])
            ->get()
            ->map(fn (UserSanction $sanction): array => [
                'id' => $sanction->id,
                'type' => $sanction->type,
                'reason' => $sanction->reason,
                'issued_by' => $sanction->issuer?->name,
                'expires_at' => $sanction->expires_at?->toISOString(),
                'created_at' => $sanction->created_at->toISOString(),
            ])
            ->values()
            ->all();
    }

    public function isUploadBanned(User $user): bool
    {
        return $user->sanctions()
            ->active()
            ->uploadBans()
            ->exists();
    }

    public function isAccountLocked(User $user): bool
    {
        return $user->sanctions()
            ->active()
            ->accountLocks()
            ->exists();
    }

    public function getActiveUploadBan(User $user): ?UserSanction
    {
        return $user->sanctions()
            ->active()
            ->uploadBans()
            ->first();
    }

    public function getActiveAccountLock(User $user): ?UserSanction
    {
        return $user->sanctions()
            ->active()
            ->accountLocks()
            ->first();
    }

    private function applyUploadBan(User $user, User $issuer, int $activePoints): void
    {
        $existingBan = $this->getActiveUploadBan($user);

        if ($existingBan !== null) {
            return;
        }

        $banDays = (int) Setting::get('sanction_upload_ban_days', 7);

        UserSanction::create([
            'user_id' => $user->id,
            'type' => UserSanction::TYPE_UPLOAD_BAN,
            'reason' => __('messages.sanctions.upload_ban_reason', ['points' => $activePoints]),
            'issued_by' => $issuer->id,
            'expires_at' => now()->addDays($banDays),
        ]);
    }

    private function applyAccountLock(User $user, User $issuer, int $activePoints): void
    {
        $existingLock = $this->getActiveAccountLock($user);

        if ($existingLock !== null) {
            return;
        }

        $lockDays = (int) Setting::get('sanction_account_lock_days', 14);

        UserSanction::create([
            'user_id' => $user->id,
            'type' => UserSanction::TYPE_ACCOUNT_LOCK,
            'reason' => __('messages.sanctions.account_lock_reason', ['points' => $activePoints]),
            'issued_by' => $issuer->id,
            'expires_at' => now()->addDays($lockDays),
        ]);
    }
}
