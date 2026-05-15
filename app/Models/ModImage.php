<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mod_id', 'url', 'file_path', 'alt_text', 'sort_order'])]
class ModImage extends Model
{
    use HasFactory;

    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class);
    }
}
