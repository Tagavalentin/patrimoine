<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Paramètres JWT — voir shared/jwt/README.md
    |--------------------------------------------------------------------------
    */

    'secret' => env('JWT_SECRET'),
    'ttl' => (int) env('JWT_TTL', 60), // durée de validité en minutes
    'issuer' => env('JWT_ISSUER', 'patrimoine-auth-service'),
    'algo' => 'HS256',
];
