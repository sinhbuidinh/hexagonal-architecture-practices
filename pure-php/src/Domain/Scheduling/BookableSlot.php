<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PractitionerId;

/** A concrete bookable window (date + start/end), like clinic time-picker rows. */
final readonly class BookableSlot
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_HELD      = 'held';
    public const STATUS_CONFIRMED = 'confirmed';

    public function __construct(
        public BookableSlotId $id,
        public PractitionerId $practitionerId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public string $status = self::STATUS_AVAILABLE,
    ) {
        self::assertWindow($date, $startTime, $endTime);
    }

    /** @throws \InvalidArgumentException */
    public static function assertWindow(string $date, string $startTime, string $endTime): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('date must be YYYY-MM-DD.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
            throw new \InvalidArgumentException('start_time and end_time must be HH:MM.');
        }

        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('start_time must be before end_time.');
        }
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /** Half-open [start, end): touching edges (08:30–09:00 and 09:00–09:30) do not overlap. */
    public static function windowsOverlapOnDate(
        string $dateA,
        string $startA,
        string $endA,
        string $dateB,
        string $startB,
        string $endB,
    ): bool {
        if ($dateA !== $dateB) {
            return false;
        }

        return $startA < $endB && $startB < $endA;
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     *
     * @throws OverlappingBookableWindowException
     */
    public static function assertNoOverlapWithinBatch(array $windows): void
    {
        /** @var array<string, list<array{date: string, start_time: string, end_time: string}>> $byDate */
        $byDate = [];
        foreach ($windows as $window) {
            $date = (string) $window['date'];
            self::assertWindow($date, (string) $window['start_time'], (string) $window['end_time']);
            $byDate[$date][] = [
                'date'       => $date,
                'start_time' => (string) $window['start_time'],
                'end_time'   => (string) $window['end_time'],
            ];
        }

        foreach ($byDate as $dayWindows) {
            usort(
                $dayWindows,
                static fn (array $a, array $b): int => $a['start_time'] <=> $b['start_time'],
            );

            $count    = count($dayWindows);
            for ($i    = 1; $i < $count; ++$i) {
                $previous = $dayWindows[$i - 1];
                $current  = $dayWindows[$i];
                if ($previous['end_time'] > $current['start_time']) {
                    throw new OverlappingBookableWindowException($current, $previous);
                }
            }
        }
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $incoming
     * @param list<array{date: string, start_time: string, end_time: string}> $existing
     *
     * @throws OverlappingBookableWindowException
     */
    public static function assertNoOverlapWithExisting(array $incoming, array $existing): void
    {
        foreach ($incoming as $candidate) {
            $date      = (string) $candidate['date'];
            $startTime = (string) $candidate['start_time'];
            $endTime   = (string) $candidate['end_time'];
            self::assertWindow($date, $startTime, $endTime);

            foreach ($existing as $row) {
                if (self::windowsOverlapOnDate(
                    $date,
                    $startTime,
                    $endTime,
                    (string) $row['date'],
                    (string) $row['start_time'],
                    (string) $row['end_time'],
                )) {
                    throw new OverlappingBookableWindowException($candidate, $row);
                }
            }
        }
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     *
     * @return array{min: string, max: string}
     */
    public static function dateBounds(array $windows): array
    {
        if ($windows === []) {
            throw new \InvalidArgumentException('Cannot compute date bounds of an empty window list.');
        }

        $dates = array_map(static fn (array $window): string => (string) $window['date'], $windows);
        sort($dates);

        return ['min' => $dates[0], 'max' => $dates[array_key_last($dates)]];
    }
}
