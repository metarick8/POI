<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Judge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'chair_judge_id', 'id');
    }

    public function panelistJudges()
    {
        return $this->hasMany(Participants_panelist_judge::class, 'judge_id', 'id');
    }

    public function applications()
    {
        return $this->hasManyThrough(Application::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }
}
