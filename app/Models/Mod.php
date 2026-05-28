<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'category_id',
    'title',
    'slug',
    'description',
    'external_download_url',
    'virus_total_url',
    'download_clicks_count',
    'status',
    'rejection_reason',
    'approved_at',
    'reviewed_by',
])]
class Mod extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ModImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ModImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<Rating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
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

    /** @return HasMany<ModVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ModVersion::class)->latest();
    }

    /** @return HasMany<ModVersion, $this> */
    public function approvedVersions(): HasMany
    {
        return $this->hasMany(ModVersion::class)
            ->where('status', self::STATUS_APPROVED)
            ->latest('approved_at');
    }

    /** @return HasMany<ModVersion, $this> */
    public function pendingVersions(): HasMany
    {
        return $this->hasMany(ModVersion::class)->where('status', self::STATUS_PENDING);
    }

    /** @return HasOne<ModVersion, $this> */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(ModVersion::class)
            ->where('status', self::STATUS_APPROVED)
            ->where('is_current', true)
            ->latestOfMany('approved_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }
}
