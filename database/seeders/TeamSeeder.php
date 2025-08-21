<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['OG', 'OO', 'CG', 'CO'];

        foreach ($roles as $role) {
            Team::create([
                'role' => $role,
            ]);
        }
    }
}
