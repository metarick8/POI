<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'admin'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'user' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'debater' => [
            'driver' => 'jwt',
            'provider' => 'debaters',
        ],
        'judge' => [
            'driver' => 'jwt',
            'provider' => 'judges',
        ],
        'coach' => [
            'driver' => 'jwt',
            'provider' => 'coaches',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'debaters' => [
            'driver' => 'eloquent',
            'model' => App\Models\Debater::class,
        ],
        'judges' => [
            'driver' => 'eloquent',
            'model' => App\Models\Judge::class,
        ],
        'coaches' => [
            'driver' => 'eloquent',
            'model' => App\Models\Coach::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
