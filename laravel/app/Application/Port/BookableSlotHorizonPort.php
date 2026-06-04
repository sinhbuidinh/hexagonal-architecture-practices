<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Rolling materialization window (calendar days, inclusive from today). */
interface BookableSlotHorizonPort
{
    public function horizonDays(): int;
}
