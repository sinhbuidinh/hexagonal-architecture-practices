<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Scheduling\ClinicLunchBreak;

interface ClinicLunchBreakPort
{
    public function lunchBreak(): ?ClinicLunchBreak;
}
