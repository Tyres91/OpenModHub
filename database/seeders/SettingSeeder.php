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
        Setting::set('favicon_mode', 'auto');
        Setting::set('warning_expiry_days', '90');
        Setting::set('sanction_upload_ban_threshold', '5');
        Setting::set('sanction_upload_ban_days', '7');
        Setting::set('sanction_account_lock_threshold', '10');
        Setting::set('sanction_account_lock_days', '14');
    }
}
