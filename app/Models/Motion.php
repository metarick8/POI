<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motion extends Model
{
    use HasFactory;
    protected $fillable = [
        'sentence'
    ];


    public function sub_classifications()
    {
        return $this->belongsToMany(Sub_classification::class, 'motion_sub_classifications', 'motion_id', 'sub_classification_id')
            ->withPivot('created_at');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class, 'motion_id', 'id');
    }
}
