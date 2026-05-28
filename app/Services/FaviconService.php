<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FaviconService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function generateFromLogo(string $logoPath): void
    {
        $fullPath = storage_path('app/public/'.$logoPath);

        if (! file_exists($fullPath)) {
            return;
        }

        $image = $this->manager->read($fullPath);

        $this->generateIco($image);
        $this->generatePng($image, 16);
        $this->generatePng($image, 32);
        $this->generatePng($image, 180, 'apple-touch-icon.png');
        $this->generatePng($image, 192, 'android-chrome-192x192.png');
        $this->generatePng($image, 512, 'android-chrome-512x512.png');
        $this->generateWebmanifest();
    }

    private function generateIco($image): void
    {
        $icoPath = public_path('favicon.ico');

        $resized = $image->cover(32, 32);
        $resized->save($icoPath, 'ico');
    }

    private function generatePng($image, int $size, ?string $filename = null): void
    {
        $filename = $filename ?? "favicon-{$size}x{$size}.png";
        $path = public_path($filename);

        $resized = $image->cover($size, $size);
        $resized->save($path, 'png');
    }

    private function generateWebmanifest(): void
    {
        $manifest = [
            'name' => config('app.name', 'OpenModHub'),
            'short_name' => config('app.name', 'OpenModHub'),
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#4f46e5',
            'icons' => [
                [
                    'src' => '/android-chrome-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/android-chrome-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ];

        $path = public_path('site.webmanifest');
        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function clearGenerated(): void
    {
        $files = [
            'favicon.ico',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'apple-touch-icon.png',
            'android-chrome-192x192.png',
            'android-chrome-512x512.png',
            'site.webmanifest',
        ];

        foreach ($files as $file) {
            $path = public_path($file);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function clearAll(): void
    {
        $this->clearGenerated();
    }

    public function uploadManual(array $validatedFiles): void
    {
        if (isset($validatedFiles['favicon'])) {
            $validatedFiles['favicon']->move(public_path(), 'favicon.ico');
        }

        if (isset($validatedFiles['favicon_16'])) {
            $validatedFiles['favicon_16']->move(public_path(), 'favicon-16x16.png');
        }

        if (isset($validatedFiles['favicon_32'])) {
            $validatedFiles['favicon_32']->move(public_path(), 'favicon-32x32.png');
        }

        if (isset($validatedFiles['apple_touch'])) {
            $validatedFiles['apple_touch']->move(public_path(), 'apple-touch-icon.png');
        }

        if (isset($validatedFiles['android_192'])) {
            $validatedFiles['android_192']->move(public_path(), 'android-chrome-192x192.png');
        }

        if (isset($validatedFiles['android_512'])) {
            $validatedFiles['android_512']->move(public_path(), 'android-chrome-512x512.png');
        }
    }

    public function hasGeneratedFavicons(): bool
    {
        return file_exists(public_path('favicon.ico'));
    }
}
