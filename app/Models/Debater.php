<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Debater extends Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'coach_id',
    ];

        /**
     * Get the identifier for JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims.
     */
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

    public function applications()
    {
        return $this->hasManyThrough(Application::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function participantsDebater()
    {
        return $this->hasMany(ParticipantsDebater::class, 'debater_id', 'id');
    }
}
