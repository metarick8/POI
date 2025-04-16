<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Judge extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function wing_judges()
    {
        return $this->hasMany(Participants_wing_judge::class, 'wing_judge_id', 'id');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'main_judge_id', 'id');
    }
}
