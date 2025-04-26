<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class   Debater extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'user_id',
        'coach_id'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

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
                    ->withPivot('role_id')
                    ->using(Participants_debater::class);
    }

}
