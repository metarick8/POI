<?php

namespace App\Services\Education;

use App\Models\Faculty;

class FacultyService
{
    public function index()
    {
        return Faculty::with('university')->all();
    }

}
