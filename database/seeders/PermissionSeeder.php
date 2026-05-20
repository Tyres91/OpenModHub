<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'group' => 'moderation',
                'slug' => 'review_mods',
                'name' => 'Review Mods',
            ],
            [
                'group' => 'moderation',
                'slug' => 'moderate_comments',
                'name' => 'Moderate Comments',
            ],
            [
                'group' => 'moderation',
                'slug' => 'handle_reports',
                'name' => 'Handle Reports',
            ],
            [
                'group' => 'content',
                'slug' => 'edit_any_mod',
                'name' => 'Edit Any Mod',
            ],
            [
                'group' => 'content',
                'slug' => 'delete_any_mod',
                'name' => 'Delete Any Mod',
            ],
            [
                'group' => 'content',
                'slug' => 'manage_categories',
                'name' => 'Manage Categories',
            ],
            [
                'group' => 'content',
                'slug' => 'manage_faqs',
                'name' => 'Manage FAQs',
            ],
            [
                'group' => 'community',
                'slug' => 'manage_ranks',
                'name' => 'Manage Ranks',
            ],
            [
                'group' => 'community',
                'slug' => 'manage_users',
                'name' => 'Manage Users',
            ],
            [
                'group' => 'system',
                'slug' => 'manage_settings',
                'name' => 'Manage Settings',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                ]
            );
        }
    }
}
