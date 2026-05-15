<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mod_id', 'mod_version_id', 'provider', 'status', 'external_url', 'analysis_id', 'result_summary', 'raw_response', 'checked_at'])]
class SecurityCheck extends Model
{
    use HasFactory;

    public const PROVIDER_VIRUSTOTAL = 'virustotal';

    public const STATUS_NOT_SUBMITTED = 'not_submitted';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CLEAN = 'clean';

    public const STATUS_SUSPICIOUS = 'suspicious';

    public const STATUS_FAILED = 'failed';

    /** @return BelongsTo<Mod, $this> */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class);
    }

    /** @return BelongsTo<ModVersion, $this> */
    public function modVersion(): BelongsTo
    {
        return $this->belongsTo(ModVersion::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
