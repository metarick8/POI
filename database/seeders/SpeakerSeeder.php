<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Speaker;

class SpeakerSeeder extends Seeder
{
    public function run(): void
    {
        // Map of position => team role
        $positionToRole = [
            'Prime Minister' => 'OG',
            'Leader of Opposition' => 'OO',
            'Deputy Prime Minister' => 'OG',
            'Deputy Leader of Opposition' => 'OO',
            'Member of Government' => 'CG',
            'Member of Opposition' => 'CO',
            'Government Whip' => 'CG',
            'Opposition Whip' => 'CO',
        ];

        foreach ($positionToRole as $position => $role) {
            // Get the first team that has the required role
            $team = Team::where('role', $role)->first();

            if ($team) {
                Speaker::create([
                    'team_id' => $team->id,
                    'position' => $position,
                ]);
            }
        }
    }
}
