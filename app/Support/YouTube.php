<?php

namespace App\Support;

class YouTube
{
    public static function videoIdFromUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url((string) $url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === 'youtu.be') {
            return self::validVideoId(explode('/', $path)[0] ?? null);
        }

        if (! in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
            return null;
        }

        if ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);

            return self::validVideoId($query['v'] ?? null);
        }

        foreach (['embed/', 'shorts/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return self::validVideoId(substr($path, strlen($prefix), 11));
            }
        }

        return null;
    }

    public static function canonicalUrl(string $videoId): string
    {
        return 'https://www.youtube.com/watch?v='.$videoId;
    }

    private static function validVideoId(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $value) === 1 ? $value : null;
    }
}
