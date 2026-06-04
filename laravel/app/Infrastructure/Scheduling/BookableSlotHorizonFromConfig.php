<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduling;

use App\Application\Port\BookableSlotHorizonPort;

final readonly class BookableSlotHorizonFromConfig implements BookableSlotHorizonPort
{
    public function horizonDays(): int
    {
        $days = (int) config('hexagon.bookable_slots.horizon_days', 15);

        return $days >= 1 ? $days : 15;
    }
}
