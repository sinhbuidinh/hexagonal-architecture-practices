<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Clock;

use HexagonPractise\Application\Port\ClockPort;

final class FrozenClock implements ClockPort
{
    public function __construct(private \DateTimeImmutable $frozen)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozen;
    }

    public function advance(\DateInterval $interval): void
    {
        $this->frozen = $this->frozen->add($interval);
    }

    public function set(\DateTimeImmutable $instant): void
    {
        $this->frozen = $instant;
    }
}
