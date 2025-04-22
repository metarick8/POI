<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Judge extends Authenticatable implements JWTSubject
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

    public function wing_judges()
    {
        return $this->hasMany(Participants_wing_judge::class, 'wing_judge_id', 'id');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'main_judge_id', 'id');
    }
}
