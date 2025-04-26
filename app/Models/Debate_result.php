<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate_result extends Model
{
    protected $fillable = [
        'debate_id',
        'role_id',
        'rank',
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
