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

    // public function panelist_judges()
    // {
    //     return $this->hasMany(Participants_panelist_judge::class, 'panelist_judge_id', 'id');
    // }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'chair_judge_id', 'id');
    }
}
