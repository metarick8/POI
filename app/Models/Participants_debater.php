<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participants_debater extends Model
{
    protected $fillable = [
        'debate_id',
        'debater_id',
        'role_id',
    ];


    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'participant_debater_id', 'id');
    }
}
