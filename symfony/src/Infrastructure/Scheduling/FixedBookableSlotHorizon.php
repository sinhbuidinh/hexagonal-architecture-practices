<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduling;

use App\Application\Port\BookableSlotHorizonPort;

final readonly class FixedBookableSlotHorizon implements BookableSlotHorizonPort
{
    public function __construct(private int $days = 15)
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('horizon days must be at least 1.');
        }
    }

    public function horizonDays(): int
    {
        return $this->days;
    }
}
