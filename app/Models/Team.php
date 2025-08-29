<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public $timestamps = false;
    protected $fillable =[
        'role'
    ];

    public function speakers()
    {
        return $this->hasMany(Speaker::class, 'team_id', 'id');
    }
}
