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
        'meeting_id',
        'start_url',
        'join_url',
        'password',
        'recording_type',
        'zoom_recording_url',
        'cloudinary_recording_id',
        'cloudinary_recording_url',
        'recording_uploaded_at',
        'final_ranks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'type' => 'string',
        'status' => 'string',
        'recording_uploaded_at' => 'datetime',
        'final_ranks' => 'json',
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

    public function getApplicantsCountAttribute()
    {
        return $this->applications()->count();
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function participantsDebaters()
    {
        return $this->hasMany(ParticipantsDebater::class, 'debate_id', 'id');
    }

    public function hasRecording(): bool
    {
        return !empty($this->zoom_recording_url) || !empty($this->cloudinary_recording_url);
    }

    public function isReadyForPreparation(): bool
    {
        return $this->status === 'teamsConfirmed' &&
            $this->participantsDebaters()->count() === 8 &&
            $this->chair_judge_id !== null;
    }

    public function canStartZoomMeeting(): bool
    {
        return $this->type === 'online' &&
            $this->status === 'teamsConfirmed' &&
            empty($this->meeting_id);
    }
}
