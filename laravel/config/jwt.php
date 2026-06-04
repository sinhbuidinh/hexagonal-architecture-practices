<?php

declare(strict_types=1);

return [
    'secret'      => env('JWT_SECRET', env('APP_KEY')),
    'issuer'      => env('JWT_ISSUER', 'hexagon-practise'),
    'ttl_seconds' => (int) env('JWT_TTL', 604_800),
];
