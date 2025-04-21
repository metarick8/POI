<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Coach extends Authenticatable  implements JWTSubject
{
    protected $fillable = [
        'user_id',
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

    public function debaters()
    {
        return $this->hasMany(Debater::class, 'coach_id', 'id');
    }

    public function teams()
    {
        return $this->belongsToMany(Debater::class, 'coach_teams', 'coach_id', 'debater_id');
    }
}
