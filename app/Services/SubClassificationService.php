<?php

namespace App\Services;

use App\Models\Sub_classification;

class SubClassificationService
{
    public function index()
    {
        return Sub_classification::with('classification')->get();
    }
}
