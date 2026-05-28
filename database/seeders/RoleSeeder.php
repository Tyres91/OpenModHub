<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'User', 'slug' => 'user'],
        ])->each(fn (array $role) => Role::query()->updateOrCreate(
            ['slug' => $role['slug']],
            ['name' => $role['name']],
        ));

        Role::query()->where('slug', 'editor')->delete();
    }
}
