<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{
    protected $fillable = [
        'sort_id',
        'sentence',
    ];

    public function sort()
    {
        return $this->belongsTo(Resolution_sort::class, 'sort_id', 'id');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'resolution_id', 'id');
    }
}
