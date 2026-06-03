<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

interface ClockPort
{
    public function now(): \DateTimeImmutable;
}
