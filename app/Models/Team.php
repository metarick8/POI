<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public $timestamps = false;
    protected $fillable =[
        'role'
    ];

    public function participants()
    {
        return $this->hasMany(ParticipantsDebater::class, 'team_id', 'id');
    }


    public function speakers()
    {
        return $this->hasMany(Speaker::class, 'team_id', 'id');
    }
}
