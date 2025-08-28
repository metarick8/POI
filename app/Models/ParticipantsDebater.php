<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantsDebater extends Model
{
    protected $fillable = [
        'debate_id',
        'debater_id',
        'speaker_id',
        'rank',
        'team_number'
    ];


    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'participant_debater_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
