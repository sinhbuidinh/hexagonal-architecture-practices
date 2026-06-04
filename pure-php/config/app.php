<?php

declare(strict_types=1);

return [
    'redis_dsn' => getenv('REDIS_DSN') ?: 'redis://127.0.0.1:6379',
    'slots_key_prefix' => 'scheduling:slots:',
    'appointment_key_prefix' => 'scheduling:appointment:',
    'prescription_key_prefix' => 'prescription:',
    'expiration_zset_key' => 'expiration:queue',
    'expiration_payload_prefix' => 'expiration:payload:',
    'bookable_slot_horizon_days' => (int) (getenv('BOOKABLE_SLOT_HORIZON_DAYS') ?: 15),
    'clinic_lunch_break_enabled' => filter_var(getenv('CLINIC_LUNCH_BREAK_ENABLED') ?: 'true', FILTER_VALIDATE_BOOL),
    'clinic_lunch_break_start' => getenv('CLINIC_LUNCH_BREAK_START') ?: '12:00',
    'clinic_lunch_break_end' => getenv('CLINIC_LUNCH_BREAK_END') ?: '13:30',
];
