<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Speaker extends Model
{
    protected $fillable = [
        'position'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
