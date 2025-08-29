<?php

namespace Database\Seeders;

use App\Models\Debate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DebateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Debate::factory()->count(15)->create();
    }
}
