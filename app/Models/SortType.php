<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortType extends Model
{
    public function sorts()
    {
        return $this->hasMany(Sort::class, 'sort_type_id', 'id');
    }

}
