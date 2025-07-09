<?php

namespace App\Http\Controllers;

use App\Services\Education\FacultyService;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    protected $facultyService;

    public function __construct(FacultyService $facultyService)
    {
        $this->facultyService = $facultyService;
    }

    
}
