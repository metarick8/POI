<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sub_classification extends Model
{
    protected $table = 'sub_classifications';
    protected $fillable = [
        'name',
        'classification_id'
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class, 'classification_id', 'id');
    }

    public function motions()
    {
        return $this->belongsToMany(Motion::class, 'motion_sub_classifications', 'sub_classification_id', 'motion_id');
    }
}
