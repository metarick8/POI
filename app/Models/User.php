<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'profile_picture_url',
        'email',
        'password',
        'pp_public_id',
        'faculty_id',
        'governorate',
        'mobile_number',
        'education_degree',
        'birth_date',
        'role', // admin, user
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'education_degree' => 'string',
            'role' => 'string',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }

    public function debater()
    {
        return $this->hasOne(Debater::class, 'user_id', 'id');
    }

    public function coach()
    {
        return $this->hasOne(Coach::class, 'user_id', 'id');
    }

    public function judge()
    {
        return $this->hasOne(Judge::class, 'user_id', 'id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'user_id', 'id');
    }
}
