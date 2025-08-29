<?php

namespace App\Console\Commands;

use App\Services\DebateService;
use App\Services\ZoomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDebatePreparation extends Command
{
    protected $signature = 'debate:check-preparation';
    protected $description = 'Check debates that need to move to preparation phase (15 minutes before start)';

    protected $debateService;
    protected $zoomService;

    public function __construct(DebateService $debateService, ZoomService $zoomService)
    {
        parent::__construct();
        $this->debateService = $debateService;
        $this->zoomService = $zoomService;
    }

    public function handle()
    {
        $this->info('Checking debates for preparation phase...');

        try {
            // Check debates that need to move to preparation
            $preparationResults = $this->debateService->checkDebatesForPreparation();
            $preparationCount = count(array_filter($preparationResults, fn($r) => $r['success']));

            if ($preparationCount > 0) {
                $this->info("Moved {$preparationCount} debates to preparation phase");
                
                // Create Zoom meetings for online debates in preparation
                $this->info('Creating Zoom meetings for online debates...');
                $zoomResults = $this->zoomService->checkAndCreateUpcomingMeetings();
                $zoomCount = count(array_filter($zoomResults, fn($r) => $r['success']));
                
                if ($zoomCount > 0) {
                    $this->info("Created {$zoomCount} Zoom meetings");
                } else {
                    $this->info('No Zoom meetings created');
                }
            } else {
                $this->info('No debates ready for preparation phase');
            }

            // Log results
            Log::info('Debate preparation check completed', [
                'preparation_results' => $preparationResults,
                'zoom_results' => $zoomResults ?? []
            ]);

            $this->info('Debate preparation check completed successfully');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error during debate preparation check: ' . $e->getMessage());
            Log::error('Debate preparation check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}