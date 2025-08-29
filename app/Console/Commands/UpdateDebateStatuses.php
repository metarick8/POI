<?php

namespace App\Console\Commands;

use App\Models\Debate;
use App\Services\DebateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDebateStatuses extends Command
{
    protected $signature = 'debate:update-statuses';
    protected $description = 'Update debate statuses based on current time and participant counts';

    protected $debateService;

    public function __construct(DebateService $debateService)
    {
        parent::__construct();
        $this->debateService = $debateService;
    }

    public function handle()
    {
        $this->info('Updating debate statuses...');

        try {
            $debates = Debate::whereIn('status', ['announced', 'playersConfirmed', 'teamsConfirmed', 'debatePreparation'])
                            ->get();

            $updated = 0;
            
            foreach ($debates as $debate) {
                $originalStatus = $debate->status;
                $result = $this->debateService->updateStatus($debate);
                
                if (!is_string($result) && $result->status !== $originalStatus) {
                    $updated++;
                    $this->info("Debate {$debate->id}: {$originalStatus} -> {$result->status}");
                }
            }

            $this->info("Updated {$updated} debate statuses");
            
            Log::info('Debate status update completed', [
                'total_debates' => $debates->count(),
                'updated_count' => $updated
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error updating debate statuses: ' . $e->getMessage());
            Log::error('Debate status update failed', [
                'error' => $e->getMessage()
            ]);
            
            return Command::FAILURE;
        }
    }
}