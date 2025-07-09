<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        //$sortTypes = ['Type', 'Sort'];
        $sortTypes = ['Subject', 'Form'];
        foreach ($sortTypes as $name)
        Classification::create(['name' => $name]);
    }
}
