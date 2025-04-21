<?php

use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateCoach;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\JwtMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('auth', [
            JwtMiddleware::class,
            AuthenticateAdmin::class,
            AuthenticateCoach::class,
            AuthenticateJudge::class,
            AuthenticateDebater::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        app()->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, function ($app) {
            return new class extends \Illuminate\Foundation\Exceptions\Handler {
                public function render($request, Throwable $e)
                {
                    return response()->json([
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ], 500);
                }
            };
        });
    })
    ->create();
