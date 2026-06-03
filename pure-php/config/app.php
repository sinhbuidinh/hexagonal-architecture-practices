<?php

declare(strict_types=1);

return [
    'redis_dsn' => getenv('REDIS_DSN') ?: 'redis://127.0.0.1:6379',
    'slots_key_prefix' => 'scheduling:slots:',
    'appointment_key_prefix' => 'scheduling:appointment:',
    'prescription_key_prefix' => 'prescription:',
    'expiration_zset_key' => 'expiration:queue',
    'expiration_payload_prefix' => 'expiration:payload:',
];
