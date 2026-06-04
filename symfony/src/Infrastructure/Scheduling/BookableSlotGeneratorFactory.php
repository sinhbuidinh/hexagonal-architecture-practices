<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduling;

use App\Application\Port\ClinicLunchBreakPort;
use App\Domain\Scheduling\BookableSlotGenerator;

final readonly class BookableSlotGeneratorFactory
{
    public function __construct(
        private ClinicLunchBreakPort $lunchBreakPort,
    ) {
    }

    public function create(): BookableSlotGenerator
    {
        return new BookableSlotGenerator($this->lunchBreakPort->lunchBreak());
    }
}
