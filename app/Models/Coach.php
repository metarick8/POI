<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    protected $fillable = [
        'user_id',
    ];

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
