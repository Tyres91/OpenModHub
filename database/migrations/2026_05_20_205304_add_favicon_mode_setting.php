<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Setting::get('favicon_mode') === null) {
            Setting::set('favicon_mode', 'auto');
        }
    }

    public function down(): void
    {
        Setting::forget('favicon_mode');
    }
};
