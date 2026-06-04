<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Scheduling\BookableSlot;
use App\Domain\Shared\BookableSlotId;
use App\Domain\Shared\PractitionerId;

interface BookableSlotQueryPort
{
    /**
     * @return list<BookableSlot>
     */
    public function listAvailable(
        PractitionerId $practitionerId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array;

    public function find(BookableSlotId $slotId): ?BookableSlot;

    /**
     * All persisted windows in the inclusive date range (any status) — for overlap checks before insert.
     *
     * @return list<array{date: string, start_time: string, end_time: string}>
     */
    public function listWindowsBetween(
        PractitionerId $practitionerId,
        string $dateFrom,
        string $dateTo,
    ): array;
}
