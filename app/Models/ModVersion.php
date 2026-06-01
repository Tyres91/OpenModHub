<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'mod_id',
    'submitted_by',
    'version',
    'normalized_version',
    'changelog',
    'external_download_url',
    'virus_total_url',
    'youtube_preview_url',
    'youtube_video_id',
    'download_clicks_count',
    'status',
    'rejection_reason',
    'approved_at',
    'reviewed_by',
    'is_current',
])]
class ModVersion extends Model
{
    use HasFactory;

    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<SecurityCheck, $this> */
    public function securityChecks(): HasMany
    {
        return $this->hasMany(SecurityCheck::class);
    }

    /** @return HasOne<SecurityCheck, $this> */
    public function latestSecurityCheck(): HasOne
    {
        return $this->hasOne(SecurityCheck::class)->latestOfMany();
    }

    public function isApproved(): bool
    {
        return $this->status === Mod::STATUS_APPROVED;
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }
}
