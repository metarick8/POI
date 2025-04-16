<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resolution_sort extends Model
{
    protected $fillable = [
        'sort',
    ];

    public function resolutions()
    {
        return $this->hasMany(Resolution::class, 'sort_id', 'id');
    }
}
