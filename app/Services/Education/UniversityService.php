<?php

namespace App\Services\Education;

use App\Models\University;
use Throwable;

class UniversityService
{
 public function index()
    {
        try{
            $universities = University::with('faculties')->get();

            if($universities->isEmpty())
                return collect([]);

        } catch(Throwable $t){
            return $t->getMessage();
        }
    }

}
