<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debater extends Model
{
    protected $fillable = [
        'user_id',
        'coach_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class, 'coach_id', 'id');
    }

    public function teams()
    {
        return $this->belongsToMany(Coach::class, 'coach_teams', 'debater_id', 'coach_id');
    }

    public function participants()
    {
        return $this->belongsToMany(Debater::class, 'participants_debaters', 'debater_id', 'debate_id')
                    ->withPivot('debater_role_id')
                    ->using(Participants_debater::class);
    }

}
