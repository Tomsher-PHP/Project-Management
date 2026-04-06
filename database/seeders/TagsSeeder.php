<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Tag::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $tags = [
            ['name' => 'E-commerce', 'slug' => 'e-commerce'],
            ['name' => 'Fintech', 'slug' => 'fintech'],
            ['name' => 'CRM', 'slug' => 'crm'],
            ['name' => 'SaaS', 'slug' => 'saas'],
            ['name' => 'Internal', 'slug' => 'internal'],
            ['name' => 'Mobile App', 'slug' => 'mobile-app'],
            ['name' => 'Web App', 'slug' => 'web-app'],
            ['name' => 'Roles', 'slug' => 'roles'],
            ['name' => 'Permissions', 'slug' => 'permissions'],
            ['name' => 'Authorization', 'slug' => 'authorization'],
            ['name' => 'Backend', 'slug' => 'backend'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag + [
                'color' => null,
                'type' => 'general',
                'is_active' => true,
                'is_system' => true,
            ]);
        }
    }
}
