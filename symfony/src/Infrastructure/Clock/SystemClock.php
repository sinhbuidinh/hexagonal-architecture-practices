<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Application\Port\ClockPort;

final class SystemClock implements ClockPort
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
