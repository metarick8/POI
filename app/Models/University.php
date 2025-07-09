<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $fillable = [
        'name'
    ];

    public function faculties()
    {
        return $this->hasMany(Faculty::class, 'faculty_id', 'id');
    }
}
