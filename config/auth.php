<?php

return [
    // Guard por defecto (session con provider 'users') //
    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    // Guard web basado en sesión (Sanctum maneja los tokens de API) //
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],

    // Provider apunta a nuestro modelo Usuario (no al User genérico) //
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Usuario::class,
        ],
    ],

    // Config de reset password (no la usamos, pero Laravel la requiere) //
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    // Segundos que Laravel pide la contraseña de nuevo antes de acciones sensibles //
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
