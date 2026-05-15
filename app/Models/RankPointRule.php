<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'label', 'points', 'threshold', 'is_enabled'])]
class RankPointRule extends Model
{
    use HasFactory;

    public const COMMENT_CREATED = 'comment_created';

    public const APPROVED_MOD = 'approved_mod';

    public const APPROVED_VERSION = 'approved_version';

    public const DOWNLOAD_THRESHOLD = 'download_threshold';

    public const RATING_RECEIVED = 'rating_received';

    public const RATING_AVERAGE_BONUS = 'rating_average_bonus';

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'threshold' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
