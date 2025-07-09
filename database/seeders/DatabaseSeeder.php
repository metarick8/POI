<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ClassificationSeeder::class,
            SubClassificationSeeder::class,
            MotionsSeeder::class,
            MotionSubClassificationSeeder::class,
            ColorSeeder::class,
            UniversitySeeder::class
        ]);
    }
}
