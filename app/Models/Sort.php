<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sort extends Model
{
    protected $fillable = [
        'name',
        'sort_type_id'
    ];

    public function sort_type()
    {
        return $this->belongsTo(SortType::class, 'sort_type_id', 'id');
    }

    public function resolutions()
    {
        return $this->belongsToMany(Resolution::class, 'resolution_sorts', 'sort_id', 'resolution_id');
    }
}
