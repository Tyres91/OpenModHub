<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'subject_en',
    'subject_de',
    'body_en',
    'body_de',
    'is_active',
])]
class EmailTemplate extends Model
{
    use HasFactory;

    public const KEY_VERIFY_EMAIL = 'verify_email';
    public const KEY_MOD_APPROVED = 'mod_approved';
    public const KEY_MOD_REJECTED = 'mod_rejected';
    public const KEY_VERSION_APPROVED = 'version_approved';
    public const KEY_VERSION_REJECTED = 'version_rejected';

    public const PLACEHOLDERS = [
        self::KEY_VERIFY_EMAIL => [
            '{user_name}' => 'User name',
            '{verification_url}' => 'Email verification URL',
            '{cta_text}' => 'CTA button text',
            '{cta_url}' => 'CTA button URL',
            '{site_name}' => 'Site name',
            '{site_url}' => 'Site URL',
        ],
        self::KEY_MOD_APPROVED => [
            '{user_name}' => 'User name',
            '{mod_title}' => 'Mod title',
            '{mod_url}' => 'Mod URL',
            '{mod_slug}' => 'Mod slug',
            '{reviewer_name}' => 'Reviewer name',
            '{cta_text}' => 'CTA button text',
            '{cta_url}' => 'CTA button URL',
            '{site_name}' => 'Site name',
            '{site_url}' => 'Site URL',
        ],
        self::KEY_MOD_REJECTED => [
            '{user_name}' => 'User name',
            '{mod_title}' => 'Mod title',
            '{mod_url}' => 'Mod URL',
            '{mod_slug}' => 'Mod slug',
            '{rejection_reason}' => 'Rejection reason',
            '{reviewer_name}' => 'Reviewer name',
            '{cta_text}' => 'CTA button text',
            '{cta_url}' => 'CTA button URL',
            '{site_name}' => 'Site name',
            '{site_url}' => 'Site URL',
        ],
        self::KEY_VERSION_APPROVED => [
            '{user_name}' => 'User name',
            '{mod_title}' => 'Mod title',
            '{mod_url}' => 'Mod URL',
            '{mod_slug}' => 'Mod slug',
            '{version}' => 'Version number',
            '{reviewer_name}' => 'Reviewer name',
            '{cta_text}' => 'CTA button text',
            '{cta_url}' => 'CTA button URL',
            '{site_name}' => 'Site name',
            '{site_url}' => 'Site URL',
        ],
        self::KEY_VERSION_REJECTED => [
            '{user_name}' => 'User name',
            '{mod_title}' => 'Mod title',
            '{mod_url}' => 'Mod URL',
            '{mod_slug}' => 'Mod slug',
            '{version}' => 'Version number',
            '{rejection_reason}' => 'Rejection reason',
            '{reviewer_name}' => 'Reviewer name',
            '{cta_text}' => 'CTA button text',
            '{cta_url}' => 'CTA button URL',
            '{site_name}' => 'Site name',
            '{site_url}' => 'Site URL',
        ],
    ];

    public static function get(string $key): ?self
    {
        return self::query()->where('key', $key)->where('is_active', true)->first();
    }

    public function getSubject(string $locale = 'en'): string
    {
        return $locale === 'de' ? $this->subject_de : $this->subject_en;
    }

    public function getBody(string $locale = 'en'): string
    {
        return $locale === 'de' ? $this->body_de : $this->body_en;
    }

    public function renderBody(array $data, string $locale = 'en'): string
    {
        $body = $this->getBody($locale);

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $body = str_replace($placeholder, (string) $value, $body);
        }

        return $body;
    }

    /** @return array<string, string> */
    public function getAvailablePlaceholdersAttribute(): array
    {
        return self::PLACEHOLDERS[$this->key] ?? [];
    }
}
