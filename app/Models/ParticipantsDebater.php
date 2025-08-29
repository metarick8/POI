<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantsDebater extends Model
{
    protected $table = 'participants_debaters';
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

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }

    public function debater()
    {
        return $this->belongsTo(Debater::class, 'debater_id');
    }

    public function speaker()
    {
        return $this->belongsTo(Speaker::class, 'speaker_id', 'id');
    }
}
