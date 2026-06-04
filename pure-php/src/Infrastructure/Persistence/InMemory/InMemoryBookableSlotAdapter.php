<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\InMemory;

use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\BookableSlotQueryPort;
use HexagonPractise\Domain\Scheduling\BookableSlot;
use HexagonPractise\Domain\Scheduling\BookableSlotNotFoundException;
use HexagonPractise\Domain\Scheduling\BookableSlotUnavailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PractitionerId;

final class InMemoryBookableSlotAdapter implements BookableSlotCommandPort, BookableSlotQueryPort
{
    /** @var array<int, BookableSlot> */
    private array $slots = [];

    private int $nextId = 1;

    public function publish(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        string $endTime,
    ): BookableSlot {
        $this->assertCanPublish($practitionerId, [
            ['date' => $date, 'start_time' => $startTime, 'end_time' => $endTime],
        ]);

        $id     = new BookableSlotId($this->nextId++);
        $stored = new BookableSlot(
            id            : $id,
            practitionerId: $practitionerId,
            date          : $date,
            startTime     : $startTime,
            endTime       : $endTime,
            status        : BookableSlot::STATUS_AVAILABLE,
        );
        $this->slots[$id->value] = $stored;

        return $stored;
    }

    public function replaceAvailableFrom(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        array $windows,
    ): array {
        BookableSlot::assertNoOverlapWithinBatch($windows);

        return [
            'deleted'  => $this->deleteAvailableStartingAfter($practitionerId, $date, $startTime),
            'inserted' => $this->publishMany($practitionerId, $windows),
        ];
    }

    public function publishMany(PractitionerId $practitionerId, array $windows): int
    {
        if ($windows === []) {
            return 0;
        }

        $this->assertCanPublish($practitionerId, $windows);

        $inserted = 0;
        foreach ($windows as $window) {
            $this->publish(
                $practitionerId,
                (string) $window['date'],
                (string) $window['start_time'],
                (string) $window['end_time'],
            );
            ++$inserted;
        }

        return $inserted;
    }

    public function deleteAvailableStartingAfter(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
    ): int {
        $deleted = 0;
        foreach ($this->slots as $id => $slot) {
            if ($slot->practitionerId->value !== $practitionerId->value) {
                continue;
            }
            if ($slot->status !== BookableSlot::STATUS_AVAILABLE) {
                continue;
            }
            if ($slot->date > $date || ($slot->date === $date && $slot->startTime >= $startTime)) {
                unset($this->slots[$id]);
                ++$deleted;
            }
        }

        return $deleted;
    }

    public function listAvailable(
        PractitionerId $practitionerId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $result = [];
        foreach ($this->slots as $slot) {
            if ($slot->practitionerId->value !== $practitionerId->value) {
                continue;
            }
            if ($slot->status !== BookableSlot::STATUS_AVAILABLE) {
                continue;
            }
            if ($dateFrom !== null && $slot->date < $dateFrom) {
                continue;
            }
            if ($dateTo !== null && $slot->date > $dateTo) {
                continue;
            }
            $result[] = $slot;
        }

        usort($result, static fn (BookableSlot $a, BookableSlot $b): int => [$a->date, $a->startTime] <=> [$b->date, $b->startTime]);

        return $result;
    }

    public function find(BookableSlotId $slotId): ?BookableSlot
    {
        return $this->slots[$slotId->value] ?? null;
    }

    public function listWindowsBetween(
        PractitionerId $practitionerId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $windows = [];
        foreach ($this->slots as $slot) {
            if ($slot->practitionerId->value !== $practitionerId->value) {
                continue;
            }
            if ($slot->date < $dateFrom || $slot->date > $dateTo) {
                continue;
            }
            $windows[] = self::windowFromSlot($slot);
        }

        return $windows;
    }

    public function markHeld(BookableSlotId $slotId, AppointmentId $appointmentId): void
    {
        $slot = $this->requireSlot($slotId);
        if (!$slot->isAvailable()) {
            throw new BookableSlotUnavailableException($slotId, $slot->status);
        }

        $this->slots[$slotId->value] = new BookableSlot(
            id            : $slot->id,
            practitionerId: $slot->practitionerId,
            date          : $slot->date,
            startTime     : $slot->startTime,
            endTime       : $slot->endTime,
            status        : BookableSlot::STATUS_HELD,
        );
    }

    public function release(BookableSlotId $slotId): void
    {
        $slot = $this->requireSlot($slotId);
        $this->slots[$slotId->value] = new BookableSlot(
            id            : $slot->id,
            practitionerId: $slot->practitionerId,
            date          : $slot->date,
            startTime     : $slot->startTime,
            endTime       : $slot->endTime,
            status        : BookableSlot::STATUS_AVAILABLE,
        );
    }

    public function markConfirmed(BookableSlotId $slotId): void
    {
        $slot = $this->requireSlot($slotId);
        $this->slots[$slotId->value] = new BookableSlot(
            id            : $slot->id,
            practitionerId: $slot->practitionerId,
            date          : $slot->date,
            startTime     : $slot->startTime,
            endTime       : $slot->endTime,
            status        : BookableSlot::STATUS_CONFIRMED,
        );
    }

    private function requireSlot(BookableSlotId $slotId): BookableSlot
    {
        $slot = $this->find($slotId);
        if ($slot === null) {
            throw new BookableSlotNotFoundException($slotId);
        }

        return $slot;
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     */
    private function assertCanPublish(PractitionerId $practitionerId, array $windows): void
    {
        BookableSlot::assertNoOverlapWithinBatch($windows);

        $bounds   = BookableSlot::dateBounds($windows);
        $existing = $this->listWindowsBetween($practitionerId, $bounds['min'], $bounds['max']);

        BookableSlot::assertNoOverlapWithExisting($windows, $existing);
    }

    /** @return array{date: string, start_time: string, end_time: string} */
    private static function windowFromSlot(BookableSlot $slot): array
    {
        return [
            'date'       => $slot->date,
            'start_time' => $slot->startTime,
            'end_time'   => $slot->endTime,
        ];
    }
}
