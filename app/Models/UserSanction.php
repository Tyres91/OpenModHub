<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'reason', 'issued_by', 'expires_at', 'removed_by', 'removed_at'])]
class UserSanction extends Model
{
    public const TYPE_UPLOAD_BAN = 'upload_ban';
    public const TYPE_ACCOUNT_LOCK = 'account_lock';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function remover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->removed_at === null && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('removed_at')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeUploadBans(Builder $query): void
    {
        $query->where('type', self::TYPE_UPLOAD_BAN);
    }

    public function scopeAccountLocks(Builder $query): void
    {
        $query->where('type', self::TYPE_ACCOUNT_LOCK);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }
}
