<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\Participants_wing_judge;
use Illuminate\Support\Facades\DB;
use Throwable;

class DebateService
{

    public function create($request)
    {
        // DB::beginTransaction();
        // try {
        //     $debate = Debate::create([
        //         'resolution_id' => $request->get('resolution_id'),
        //         'main_judge_id' => $request->get('main_judge_id'),
        //         'start_date' => $request->get('date'),
        //     ]);
        //     foreach ($request->get('wing_judges') as $judge_id)
        //         Participants_wing_judge::create([
        //             'debate_id' => $debate->id,
        //             'wing_judge_id' => $judge_id
        //         ]);
        //     DB::commit();
        //     return $debate;
        // } catch (Throwable $t) {
        //     DB::rollBack();
        //     return $t->getMessage();
        // }
    }


}
