<?php

namespace App\Services;

use App\Models\Motion;
use App\Models\Motion_sub_classification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class MotionService
{
    public function index()
    {
        try {
            $motions = Motion::with('sub_classifications')->get();

            if ($motions->isEmpty()) {
                return collect([]);
            }

            return $motions;
        } catch (Throwable $t) {
            return $t->getMessage();
        }
    }

    public function create($request)
    {
        DB::beginTransaction();
        try {
            $motion = Motion::create([
                "sentence" => $request->get('sentence')
            ]);

            foreach ($request->get('sub_classifications') as $subClassificationId) {
                Motion_sub_classification::create([
                    'motion_id' => $motion->id,
                    'sub_classification_id' => $subClassificationId
                ]);
            }

            DB::commit();
            return true;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function patch($request)
    {
        DB::beginTransaction();
        try {

            $motion = Motion::findOrFail($request->get("motion_id"));

            if ($request->has('sentence')) {
                $motion->sentence = $request->get('sentence');
                $motion->touch();
                $motion->save();
            }

            if ($request->has('sub_classifications')) {
                $motion->sub_classifications()->detach();

                foreach ($request->get('sub_classifications') as $subClassificationId) {
                    $motion->sub_classifications()->attach($subClassificationId);
                }
                $motion->touch();
            }

            DB::commit();
            return true;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }

    public function delete($motionId)
    {
        DB::beginTransaction();
        try {
            $motion = Motion::findOrFail($motionId);
            $debates = $motion->debates()->get();

            if ($debates->isEmpty()) {
                $motion->delete();
            } else {
                $canDelete = true;
                foreach ($debates as $debate) {
                    if ($debate->start_date === null || Carbon::parse($debate->start_date)->gt(now()->addDay())) {
                        continue;
                    }
                    $canDelete = false;
                    break;
                }

                if ($canDelete) {
                    //$debate->delete();
                    $motion->delete();
                } else {
                    throw new Exception('Cannot delete motion as it has started debates');
                }
            }

            DB::commit();
            return true;
        } catch (Throwable $t) {
            DB::rollBack();
            return $t->getMessage();
        }
    }
}
