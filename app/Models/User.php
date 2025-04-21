<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $guard = 'web';
    protected $fillable = [
        'first_name',
        'last_name',
        'profile_picture',
        'email',
        'password'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function debater()
    {
        return $this->hasOne(Debater::class, 'user_id', 'id');
    }

    public function coach():HasOne
    {
        return $this->hasOne(Coach::class, 'user_id', 'id');
    }

    public function judge()
    {
        return $this->hasOne(Judge::class, 'user_id', 'id');
    }
}
