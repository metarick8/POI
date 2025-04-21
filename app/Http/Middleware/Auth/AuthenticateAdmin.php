<?php

namespace App\Http\Middleware\Auth;

use App\Http\Middleware\JwtMiddleware;

class AuthenticateAdmin extends JwtMiddleware
{
    public function __construct()
    {
        parent::__construct('admin');
    }
}
