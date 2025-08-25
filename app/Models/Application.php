<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'debate_id',
        'status',
        'type'
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class, 'debate_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
