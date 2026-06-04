<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduling;

use App\Application\Port\ClinicLunchBreakPort;
use App\Domain\Scheduling\ClinicLunchBreak;

final readonly class ClinicLunchBreakFromConfig implements ClinicLunchBreakPort
{
    public function lunchBreak(): ?ClinicLunchBreak
    {
        if (! (bool) config('hexagon.bookable_slots.lunch_break.enabled', true)) {
            return null;
        }

        return new ClinicLunchBreak(
            startTime: (string) config('hexagon.bookable_slots.lunch_break.start', '12:00'),
            endTime  : (string) config('hexagon.bookable_slots.lunch_break.end', '13:30'),
        );
    }
}
