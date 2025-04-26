<?php

namespace Database\Seeders;

use App\Models\Resolution;
use App\Models\Sort;
use Illuminate\Database\Seeder;

class ResolutionSortsSeeder extends Seeder
{
    public function run()
    {
        $resolutions = Resolution::all();
        $sorts = Sort::all();

        foreach ($resolutions as $resolution) {
            $assignedSorts = $sorts->random(rand(1, 2))->pluck('id')->toArray();
            $resolution->sorts()->attach($assignedSorts);
        }
    }
}
