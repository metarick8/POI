<?php

namespace Database\Seeders;

use App\Models\Sort;
use App\Models\SortType;
use App\Models\Sub_classification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubClassificationSeeder extends Seeder
{
    public function run()
    {
        $subjects = [
            'Economic',
            'Commercial',
            'Political',
            'Social',
            'Educational',
            'Environmental',
            'Technical',
            'Athlete',
            'Legal',
            'Cultural',
            'Technology',
            'philosophical'
        ];

        $forms = [
            'Policy',
            'Advocacy',
            'Regret',
            'Counter-Narrative',
            'Comparative',
            'Worldbuilding',
            'takeholder-Framed',
            'Scenario'
        ];

        foreach ($subjects as $subject)
            Sub_classification::create([
                'name' => $subject,
                'classification_id' => 1,
            ]);

        foreach ($forms as $form)
            Sub_classification::create([
                'name' => $form,
                'classification_id' => 2,
            ]);
    }
}
