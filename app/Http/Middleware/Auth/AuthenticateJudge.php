<?php

namespace App\Http\Middleware\Auth;

use App\Http\Middleware\JwtMiddleware;

class AuthenticateJudge extends JwtMiddleware
{
    public function __construct()
    {
        parent::__construct('judge');
    }
}
