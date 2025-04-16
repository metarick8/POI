<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate extends Model
{
    protected $fillable = [
        'resolution_id',
        'main_judge_id',
        'start_date',
    ];


    public function debaters()
    {
        return $this->belongsToMany(Debater::class, 'participants_debaters', 'debate_id', 'debater_id')
                    ->withPivot('debater_role_id')
                    ->using(Participants_debater::class);
    }

    // To get debaters with a specific role
    public function debatersWithRole($roleId)
    {
        return $this->debaters()
                   ->wherePivot('debater_role_id', $roleId);
    }

    public function results()
    {
        return $this->hasMany(Debate_result::class, 'debate_id', 'id');
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class, 'resolution_id', 'id');
    }

    public function wing_judges()
    {
        return $this->hasMany(Participants_wing_judge::class, 'debate_id', 'id');
    }

    public function main_judge()
    {
        return $this->belongsTo(Judge::class, 'main_judge_id', 'id');
    }
}
