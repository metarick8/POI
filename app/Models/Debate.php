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
        'judge_count',
        'debater_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'type' => 'string',
        'status' => 'string',
    ];

    public function motion()
    {
        return $this->belongsTo(Motion::class);
    }

    public function chairJudge()
    {
        return $this->belongsTo(Judge::class, 'chair_judge_id', 'id');
    }

    public function panelistJudges()
    {
        return $this->hasMany(Participants_panelist_judge::class, 'debate_id', 'id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'debate_id', 'id');
    }

    public function debaters()
    {
        return $this->belongsToMany(User::class, 'applications', 'debate_id', 'user_id')
            ->where('applications.status', 'approved')
            ->where('applications.type', 'debater');
    }

    public function judges()
    {
        return $this->belongsToMany(User::class, 'applications', 'debate_id', 'user_id')
            ->where('applications.status', 'approved')
            ->whereIn('applications.type', ['chair_judge', 'panelist_judge']);
    }

    public function getDebaterCountAttribute()
    {
        return $this->debaters()->count();
    }

    public function getJudgeCountAttribute()
    {
        return $this->judges()->count();
    }
}
