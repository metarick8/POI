<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debater extends Model
{
    protected $fillable = [
        'user_id',
        'coach_id',
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

    public function applications()
    {
        return $this->hasManyThrough(Application::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }
    public function participantsDebater()
    {
        return $this->hasMany(ParticipantsDebater::class, 'user_id', 'user_id');
    }   
}
