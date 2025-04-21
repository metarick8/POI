<?php

namespace App\Http\Middleware\Auth;

use App\Http\Middleware\JwtMiddleware;

class AuthenticateCoach extends JwtMiddleware
{
    public function __construct()
    {
        parent::__construct('coach');
    }
}
