<?php

use App\Models\Debate;
use App\Services\DebateService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $debates = Debate::whereIn('status', ['announced', 'applied'])->get();
    $debateService = app(DebateService::class);
    foreach ($debates as $debate) {
        $debateService->updateStatus($debate);
    }
})->everyMinute()->name('update-debate-status')->withoutOverlapping();


Artisan::command('debates:update-status {debate}', function (Debate $debate) {
    $debateService = app(DebateService::class);
    $result = $debateService->updateStatus($debate);

    if (is_string($result)) {
        $this->error("Failed to update debate {$debate->id}: {$result}");
    } else {
        $this->info("Debate {$debate->id} updated to {$result->status}");
    }
})->purpose('Update the status of a specific debate');

Schedule::call(function () {
    Log::info('Running update-debate-status task', [
        'timezone' => now()->timezone->getName(),
        'time' => now(),
    ]);
    $debates = Debate::whereIn('status', ['announced', 'applied'])->get();
    $debateService = app(DebateService::class);
    foreach ($debates as $debate) {
        $debateService->updateStatus($debate);
    }
})->everyMinute()->name('update-debate-status')->withoutOverlapping();
