<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->where('slug', 'editor')->delete();
    }

    public function down(): void
    {
        Role::query()->updateOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editor'],
        );
    }
};
