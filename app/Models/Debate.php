<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate extends Model
{
    protected $fillable = [
        'motion_id',
        'chair_judge_id',
        'start_date',
        'start_time',
        'type',
        'status',
        'filter',
        'winner',
        'summary',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string',
    ];

    public function motion()
    {
        return $this->belongsTo(Motion::class, 'motion_id');
    }

    public function chairJudge()
    {
        return $this->belongsTo(Judge::class, 'chair_judge_id');
    }

    // public function panelistJudges()
    // {
    //     return $this->hasMany(PanelistJudge::class, 'debate_id');
    // }

    public function participantsDebaters()
    {
        return $this->hasMany(ParticipantsDebater::class, 'debate_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'debate_id');
    }

    public function getApplicantsCountAttribute()
    {
        return $this->applications()->count();
    }

    public function getDebatersCountAttribute()
    {
        return $this->participantsDebaters()->count();
    }

    public function getPanelistJudgesCountAttribute()
    {
        return $this->panelistJudges()->count();
    }
}
