<?php

namespace App\Services;

use App\Models\Motion;
use App\Models\Motion_sub_classification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MotionService
{
    public function index()
    {
        try {
            $motions = Motion::with('sub_classifications')->get();

            if ($motions->isEmpty()) {
                Log::info('No motions found');
                return collect([]);
            }

            Log::info('Motions retrieved successfully', ['count' => $motions->count()]);
            return $motions;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve motions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function create(Request $request): Motion|Throwable
    {
        DB::beginTransaction();
        try {
            $motion = Motion::create([
                'sentence' => $request->get('sentence'),
            ]);

            // Manually insert pivot records without created_at
            $pivotData = array_map(function ($subClassificationId) use ($motion) {
                return [
                    'motion_id' => $motion->id,
                    'sub_classification_id' => $subClassificationId,
                ];
            }, $request->get('sub_classifications'));

            DB::table('motion_sub_classifications')->insert($pivotData);

            DB::commit();
            return $motion;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t;
        }
    }

    public function delete(Motion $motion)
    {
        DB::beginTransaction();

        // Log the attempt to delete the motion
        Log::info('Attempting to delete motion', [
            'motion_id' => $motion->id,
            'sentence' => $motion->sentence,
            'exists' => $motion->exists,
        ]);

        $debates = $motion->debates()->get();

        if ($debates->isNotEmpty()) {
            Log::warning('Motion deletion prevented due to associated debates', [
                'motion_id' => $motion->id,
                'debate_count' => $debates->count(),
                'debate_ids' => $debates->pluck('id')->toArray(),
            ]);
            DB::rollBack();
            return 'Cannot delete motion with associated debates';
        }

        $detached = $motion->sub_classifications()->detach();
        Log::info('Detached sub_classifications from motion', [
            'motion_id' => $motion->id,
            'detached_count' => $detached,
        ]);

        $success = $motion->delete();

        if (!$success) {
            Log::error('Failed to delete motion', [
                'motion_id' => $motion->id,
                'exists' => $motion->exists,
                'error' => 'Motion deletion failed, possibly due to database constraints or model state',
            ]);
            DB::rollBack();
            return 'Motion deletion failed';
        }

        Log::info('Motion deleted successfully', [
            'motion_id' => $motion->id,
        ]);

        DB::commit();
        return true;
    }
}
