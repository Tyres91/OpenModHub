<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('default_locale', 'en');
        Setting::set('mod_submissions_blocked', '0');
        Setting::set('mod_pending_submission_limit', '5');
        Setting::set('site_logo_path', '');
        Setting::set('site_logo_text', 'OpenModHub');
        Setting::set('site_logo_show_text', '1');
    }
}
