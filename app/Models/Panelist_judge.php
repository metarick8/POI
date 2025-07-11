<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panelist_judge extends Model
{
    protected $fillable = [
        'debate_id',
        'judge_id'
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }


    public function judge()
    {
        return $this->belongsTo(Judge::class, 'panelist_judge_id', 'id');
    }
}
