<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET', ''),
    'ttl' => (int) env('JWT_TTL', 60),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),
    'algo' => env('JWT_ALGO', 'HS256'),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'atomy-q')),
];
