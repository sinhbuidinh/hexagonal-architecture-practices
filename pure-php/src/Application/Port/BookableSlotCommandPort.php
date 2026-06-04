<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Scheduling\BookableSlot;
use HexagonPractise\Domain\Scheduling\BookableSlotNotFoundException;
use HexagonPractise\Domain\Scheduling\BookableSlotUnavailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PractitionerId;

interface BookableSlotCommandPort
{
    public function publish(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        string $endTime,
    ): BookableSlot;

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     */
    public function publishMany(PractitionerId $practitionerId, array $windows): int;

    /** Remove only {@see BookableSlot::STATUS_AVAILABLE} rows at or after the given local window. */
    public function deleteAvailableStartingAfter(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
    ): int;

    /**
     * Atomically refresh the rolling horizon: delete stale available rows, then insert new windows.
     *
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     *
     * @return array{deleted: int, inserted: int}
     */
    public function replaceAvailableFrom(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        array $windows,
    ): array;

    /**
     * @throws BookableSlotNotFoundException
     * @throws BookableSlotUnavailableException
     */
    public function markHeld(BookableSlotId $slotId, AppointmentId $appointmentId): void;

    /**
     * @throws BookableSlotNotFoundException
     */
    public function release(BookableSlotId $slotId): void;

    /**
     * @throws BookableSlotNotFoundException
     */
    public function markConfirmed(BookableSlotId $slotId): void;
}
