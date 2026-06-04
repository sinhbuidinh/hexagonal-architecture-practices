<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

/** Clinic-wide lunch window excluded from all practitioners' bookable slots. */
final readonly class ClinicLunchBreak
{
    public function __construct(
        public string $startTime,
        public string $endTime,
    ) {
        if (!preg_match('/^\d{2}:\d{2}$/', $this->startTime) || !preg_match('/^\d{2}:\d{2}$/', $this->endTime)) {
            throw new \InvalidArgumentException('Lunch break times must be HH:MM.');
        }

        if ($this->startTime >= $this->endTime) {
            throw new \InvalidArgumentException('Lunch break start_time must be before end_time.');
        }
    }

    /**
     * Work intervals inside [dayStart, dayEnd) with the lunch break carved out.
     *
     * @return list<array{start: string, end: string}>
     */
    public function subtractFromWorkWindow(string $dayStart, string $dayEnd): array
    {
        if ($dayStart >= $dayEnd) {
            return [];
        }

        if ($dayEnd <= $this->startTime || $dayStart >= $this->endTime) {
            return [['start' => $dayStart, 'end' => $dayEnd]];
        }

        $segments = [];

        if ($dayStart < $this->startTime) {
            $segmentEnd = $dayEnd < $this->startTime ? $dayEnd : $this->startTime;
            if ($dayStart < $segmentEnd) {
                $segments[] = ['start' => $dayStart, 'end' => $segmentEnd];
            }
        }

        if ($dayEnd > $this->endTime && $this->endTime > $dayStart) {
            $segmentStart = $dayStart > $this->endTime ? $dayStart : $this->endTime;
            if ($segmentStart < $dayEnd) {
                $segments[] = ['start' => $segmentStart, 'end' => $dayEnd];
            }
        }

        return $segments;
    }
}
