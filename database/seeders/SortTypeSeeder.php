<?php

namespace Database\Seeders;

use App\Models\SortType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SortTypeSeeder extends Seeder
{
    public function run(): void
    {
        $sortTypes = ['Type A', 'Type B'];
        foreach ($sortTypes as $name) {
            SortType::create(['name' => $name]);
        }
    }
}
