<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $fillable = [
        'judge_id',
        'participant_debater_id',
        'rate',
        'opinion'
    ];

    public function participantDebater()
    {
        return $this->belongsTo(ParticipantsDebater::class, 'participant_debater_id');
    }
}
