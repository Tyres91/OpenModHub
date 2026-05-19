<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_en',
        'question_de',
        'answer_en',
        'answer_de',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getQuestion(): string
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'de' => $this->question_de,
            default => $this->question_en,
        };
    }

    public function getAnswer(): string
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'de' => $this->answer_de,
            default => $this->answer_en,
        };
    }
}
