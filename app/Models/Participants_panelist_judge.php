<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participants_panelist_judge extends Model
{
    protected $table = 'panelist_judges';
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
        return $this->belongsTo(Judge::class, 'judge_id', 'id');
    }
}
