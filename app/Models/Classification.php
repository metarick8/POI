<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    public function sub_classifications()
    {
        return $this->hasMany(Sub_classification::class, 'classification_id', 'id');
    }
}
