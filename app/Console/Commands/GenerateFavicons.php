<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\FaviconService;
use Illuminate\Console\Command;

class GenerateFavicons extends Command
{
    protected $signature = 'favicon:generate';
    protected $description = 'Generate favicons from the current site logo';

    public function handle(FaviconService $faviconService): int
    {
        $logoPath = Setting::get('site_logo_path', '');

        if (empty($logoPath)) {
            $this->error('No logo configured. Upload a logo first via /admin/settings.');
            return Command::FAILURE;
        }

        $fullPath = storage_path('app/public/' . $logoPath);

        if (! file_exists($fullPath)) {
            $this->error("Logo file not found at: {$fullPath}");
            return Command::FAILURE;
        }

        $this->info("Generating favicons from logo: {$logoPath}");

        $faviconService->clearAll();
        $faviconService->generateFromLogo($logoPath);

        Setting::set('favicon_mode', 'auto');

        $this->info('Favicons generated successfully!');
        $this->line('Generated files:');
        $this->line('  - favicon.ico');
        $this->line('  - favicon-16x16.png');
        $this->line('  - favicon-32x32.png');
        $this->line('  - apple-touch-icon.png');
        $this->line('  - android-chrome-192x192.png');
        $this->line('  - android-chrome-512x512.png');
        $this->line('  - site.webmanifest');

        return Command::SUCCESS;
    }
}
