<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'points', 'reason', 'issued_by', 'status', 'removed_by', 'removed_at', 'expires_at'])]
class Warning extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REMOVED = 'removed';

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
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function (Builder $q2): void {
                    $q2->where('status', self::STATUS_ACTIVE)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
        });
    }

    public function scopeRemoved(Builder $query): void
    {
        $query->where('status', self::STATUS_REMOVED);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }
}
