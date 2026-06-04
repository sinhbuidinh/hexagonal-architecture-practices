<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

/** Rolling materialization window (calendar days, inclusive from today). */
interface BookableSlotHorizonPort
{
    public function horizonDays(): int;
}
