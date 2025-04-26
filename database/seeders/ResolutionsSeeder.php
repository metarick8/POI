<?php

namespace Database\Seeders;

use App\Models\Resolution;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResolutionsSeeder extends Seeder
{
    public function run()
    {
        $sentences = [
            "Resolution on global economic stability",
            "Agreement on cross-border trade regulations",
            "Political framework for diplomatic relations",
            "Social integration policy",
            "Cultural preservation initiative",
            "Legal amendments for business transparency",
            "Environmental sustainability treaty",
            "Technological innovation standards",
            "Military cooperation agreement",
            "Healthcare reform package",
            "Educational reforms for inclusive learning",
            "Infrastructure development strategy",
            "Cybersecurity policy enforcement",
            "Human rights protection act",
            "Renewable energy expansion plan"
        ];

        foreach ($sentences as $sentence) {
            Resolution::create(['sentence' => $sentence]);
        }
    }
}
