<?php

namespace Database\Seeders;

use App\Models\Motion;
use App\Models\Sort;
use App\Models\sub_classification;
use Illuminate\Database\Seeder;

class MotionSubClassificationSeeder extends Seeder
{
     public function run(){}
    // {
    //     $motions = Motion::all();
    //     $sorts = sub_classification::all();

    //     foreach ($motions as $motion) {
    //         $assignedSorts = $sorts->random(rand(1, 2))->pluck('id')->toArray();
    //         $motion->sub_classifications()->attach($assignedSorts);
    //     }
    // }
}
