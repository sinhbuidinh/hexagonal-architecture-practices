<?php

declare(strict_types=1);

return [
    'bookable_slots' => [
        /** Rolling materialization horizon (calendar days, inclusive from today). */
        'horizon_days' => (int) env('BOOKABLE_SLOT_HORIZON_DAYS', 15),

        /** Clinic-wide lunch break excluded from generated slots for every doctor. */
        'lunch_break' => [
            'enabled' => filter_var(env('CLINIC_LUNCH_BREAK_ENABLED', true), FILTER_VALIDATE_BOOL),
            'start'   => env('CLINIC_LUNCH_BREAK_START', '12:00'),
            'end'     => env('CLINIC_LUNCH_BREAK_END', '13:30'),
        ],
    ],
];
