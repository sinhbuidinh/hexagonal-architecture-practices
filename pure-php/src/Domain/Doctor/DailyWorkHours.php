<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Doctor;

/** Working window for one weekday (HH:MM, 24h). */
final readonly class DailyWorkHours
{
    public function __construct(
        public string $startTime,
        public string $endTime,
    ) {
        if (!preg_match('/^\d{2}:\d{2}$/', $this->startTime) || !preg_match('/^\d{2}:\d{2}$/', $this->endTime)) {
            throw new \InvalidArgumentException('start_time and end_time must be HH:MM.');
        }

        if ($this->startTime >= $this->endTime) {
            throw new \InvalidArgumentException('start_time must be before end_time.');
        }
    }
}
