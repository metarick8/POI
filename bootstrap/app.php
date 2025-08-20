<?php

use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateCoach;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $middleware->alias([
            'jwt.auth' => JwtMiddleware::class,
            'auth.admin' => AuthenticateAdmin::class,
            'auth.coach' => AuthenticateCoach::class,
            'auth.judge' => AuthenticateJudge::class,
            'auth.debater' => AuthenticateDebater::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // app()->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, function ($app) {
        //     return new class extends \Illuminate\Foundation\Exceptions\Handler {
        //         public function render($request, Throwable $e)
        //         {
        //             return response()->json([
        //                 'error' => $e->getMessage(),
        //                 'file' => $e->getFile(),
        //                 'line' => $e->getLine(),
        //             ], 500);
        //         }
        //     };
        // });
    })
    ->create();
