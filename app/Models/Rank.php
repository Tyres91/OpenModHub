<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'required_published_mods', 'required_points', 'color', 'icon', 'is_special'])]
class Rank extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'required_points' => 'integer',
            'required_published_mods' => 'integer',
            'is_special' => 'boolean',
        ];
    }
}
