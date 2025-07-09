<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniversitySeeder extends Seeder
{

    public function run(): void
    {
         DB::table('universities')->insert([
            ['name' => 'Damascus University']
        ]);

        // Seed faculties (assuming university_id = 1)
        DB::table('faculties')->insert([
            ['university_id' => 1, 'name' => 'Faculty of Information Technology Engineering']
        ]);
    }
}
