<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{
    protected $fillable = [
        'sentence'
    ];

    public function sorts()
    {
        return $this->belongsToMany(Sort::class, 'resolution_sorts', 'resolution_id', 'sort_id');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'resolution_id', 'id');
    }
}
