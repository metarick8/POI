<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debater_role extends Model
{
    public function participants()
    {
        return $this->hasMany(Participants_debater::class, 'debater_role_id', 'id');
    }

    public function results()
    {
        return $this->hasMany(Debate_result::class, 'debater_role_id', 'id');
    }
}
