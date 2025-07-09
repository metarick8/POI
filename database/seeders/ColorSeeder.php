<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $profileColors = [
            '#229F7A', // Teal Green
            '#7425D3', // Deep Violet
            '#217137', // Forest Green
            '#EA8D43', // Sunset Orange
            '#A565C5', // Amethyst
            '#6D8B9D', // Slate Blue
            '#F1D35F', // Mustard Yellow
            '#953943', // Burnt Rose
            '#8E7DA1', // Lavender Gray
            '#E1653F', // Terracotta
            '#DC5F5F', // Coral Red
            '#89DD95', // Light Mint
            '#CF3F65', // Raspberry
            '#554A8C', // Grape Purple
            '#07BC70', // Emerald Green
            '#1C61B6', // Royal Blue
            '#E36EA4', // Rose Pink
            '#5B9CD6', // Blue Gray
            '#AA6C6C', // Dusty Rose
            '#B30817', // Carmine Red
        ];

        DB::table('colors')->insert(array_map(function($color) {
            return [
                'code' => $color
            ];
        }, $profileColors));
    }
    }
