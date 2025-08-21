<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function assignTeams(Debate $debate, array $assignments): void
    {
        // Business rule: 2 users per team
        $teamCounts = collect($assignments)
            ->groupBy('team_id')
            ->map->count();

        if ($teamCounts->filter(fn($count) => $count !== 2)->isNotEmpty()) {
            abort(422, 'Each team must have exactly 2 users.');
        }

        DB::transaction(function () use ($assignments, $debate) {
            foreach ($assignments as $assignment) {
                $debate->applications()
                    ->where('user_id', $assignment['user_id'])
                    ->update([
                        'status' => 'accepted',
                        'team_id' => $assignment['team_id'],
                        'updated_at' => now(),
                    ]);
            }

            $debate->update(['status' => 'teams_assigned']);
        });
    }
}
