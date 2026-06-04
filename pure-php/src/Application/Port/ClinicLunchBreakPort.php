<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Scheduling\ClinicLunchBreak;

interface ClinicLunchBreakPort
{
    public function lunchBreak(): ?ClinicLunchBreak;
}
