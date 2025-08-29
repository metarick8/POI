<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'participant_debater_id',
        'note',
    ];


    public function participant()
    {
        return $this->belongsTo(ParticipantsDebater::class, 'participant_debater_id', 'id');
    }
}
