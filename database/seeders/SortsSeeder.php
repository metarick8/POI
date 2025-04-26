<?php

namespace Database\Seeders;

use App\Models\Sort;
use App\Models\SortType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SortsSeeder extends Seeder
{
    public function run()
    {
        $sorts = [
            'Economic',
            'Commercial',
            'Political',
            'Social',
            'Cultural',
            'Legal',
            'Environmental',
            'Technological',
            'Military',
            'Healthcare'
        ];

        $sortTypes = SortType::all();
        $chunks = array_chunk($sorts, 5);

        foreach ($sortTypes as $index => $sortType) {
            foreach ($chunks[$index] as $sortName) {
                Sort::create([
                    'name' => $sortName,
                    'sort_type_id' => $sortType->id
                ]);
            }
        }
    }
}
