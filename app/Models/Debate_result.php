<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate_result extends Model
{
    protected $fillable = [
        'debate_id',
        'debater_role_id',
        'rank',
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }

    public function debate_role()
    {
        return $this->belongsTo(Debater_role::class, 'debater_role_id', 'id');
    }
}
