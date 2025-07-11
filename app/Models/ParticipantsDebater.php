<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantsDebater extends Model
{
    protected $fillable = [
        'debate_id',
        'debater_id',
        'speaker_id'
    ];


    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'participant_debater_id', 'id');
    }
}
