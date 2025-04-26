<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function participants()
    {
        return $this->hasMany(Participants_debater::class, 'role_id', 'id');
    }

    public function results()
    {
        return $this->hasMany(Debate_result::class, 'role_id', 'id');
    }
}
