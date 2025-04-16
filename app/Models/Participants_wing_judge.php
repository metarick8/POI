<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participants_wing_judge extends Model
{
    protected $fillable = [
        'debate_id',
        'wing_judge_id',
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }

    public function judge()
    {
        return $this->belongsTo(Judge::class, 'wing_judge_id', 'id');
    }
}
