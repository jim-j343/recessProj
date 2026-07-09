<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GroupSeeder::class,
            TopicSeeder::class,
            PostSeeder::class,
            ModerationSeeder::class,
        ]);
    }
}
