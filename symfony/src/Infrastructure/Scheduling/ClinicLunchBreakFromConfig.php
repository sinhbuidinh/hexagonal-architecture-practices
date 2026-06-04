<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduling;

use App\Application\Port\ClinicLunchBreakPort;
use App\Domain\Scheduling\ClinicLunchBreak;

final readonly class ClinicLunchBreakFromConfig implements ClinicLunchBreakPort
{
    public function __construct(
        private bool $enabled = true,
        private string $startTime = '12:00',
        private string $endTime = '13:30',
    ) {
    }

    public function lunchBreak(): ?ClinicLunchBreak
    {
        if (!$this->enabled) {
            return null;
        }

        return new ClinicLunchBreak($this->startTime, $this->endTime);
    }
}
