<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Doctor\DoctorAppointmentSettings;

/** Pure rules: weekly hours + slot duration → concrete bookable windows. */
final class BookableSlotGenerator
{
    public function __construct(
        private ?ClinicLunchBreak $lunchBreak = null,
    ) {
    }

    /**
     * @return list<array{date: string, start_time: string, end_time: string}>
     */
    public function generate(
        DoctorAppointmentSettings $settings,
        \DateTimeImmutable $now,
        int $horizonDays,
    ): array {
        if ($horizonDays < 1) {
            throw new \InvalidArgumentException('horizonDays must be at least 1.');
        }

        $timezone  = new \DateTimeZone($settings->timezone);
        $nowLocal  = $now->setTimezone($timezone);
        $startDate = $nowLocal->setTime(0, 0);
        $endDate   = $startDate->modify('+' . ($horizonDays - 1) . ' days');

        $earliestToday = self::ceilToSlotStart($nowLocal, $settings->slotDurationMinutes);
        $windows       = [];

        for ($cursor                                      = $startDate; $cursor <= $endDate; $cursor = $cursor->modify('+1 day')) {
            $dayHours                                        = $settings->dayScheduleFor($cursor);
            if ($dayHours === null) {
                continue;
            }

            $dayStart = $dayHours->startTime;
            if ($cursor->format('Y-m-d') === $nowLocal->format('Y-m-d')) {
                $dayStart = $dayStart >= $earliestToday ? $dayStart : $earliestToday;
            }

            foreach ($this->workSegments($dayStart, $dayHours->endTime) as $segment) {
                $windows = [
                    ...$windows,
                    ...$this->splitDay(
                        date           : $cursor->format('Y-m-d'),
                        dayStart       : $segment['start'],
                        dayEnd         : $segment['end'],
                        durationMinutes: $settings->slotDurationMinutes,
                    ),
                ];
            }
        }

        return $windows;
    }

    /**
     * First materialized window (for deleting stale available slots).
     *
     * @return array{date: string, start_time: string}
     */
    public function earliestWindow(
        DoctorAppointmentSettings $settings,
        \DateTimeImmutable $now,
        int $horizonDays,
    ): array {
        $generated = $this->generate($settings, $now, $horizonDays);
        if ($generated === []) {
            $timezone = new \DateTimeZone($settings->timezone);
            $nowLocal = $now->setTimezone($timezone);

            return [
                'date'       => $nowLocal->format('Y-m-d'),
                'start_time' => self::ceilToSlotStart($nowLocal, $settings->slotDurationMinutes),
            ];
        }

        return [
            'date'       => $generated[0]['date'],
            'start_time' => $generated[0]['start_time'],
        ];
    }

    /** Round clock up to the next slot boundary (e.g. 13:56 + 15min → 14:00). */
    public static function ceilToSlotStart(\DateTimeImmutable $localNow, int $slotDurationMinutes): string
    {
        $minutesFromMidnight = (int) $localNow->format('H') * 60 + (int) $localNow->format('i');
        $seconds             = (int) $localNow->format('s');
        if ($seconds > 0 || $minutesFromMidnight % $slotDurationMinutes !== 0) {
            $minutesFromMidnight = (int) ceil($minutesFromMidnight / $slotDurationMinutes) * $slotDurationMinutes;
        }

        if ($minutesFromMidnight >= 24 * 60) {
            return '23:59';
        }

        return sprintf('%02d:%02d', intdiv($minutesFromMidnight, 60), $minutesFromMidnight % 60);
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function workSegments(string $dayStart, string $dayEnd): array
    {
        if ($this->lunchBreak === null) {
            return [['start' => $dayStart, 'end' => $dayEnd]];
        }

        return $this->lunchBreak->subtractFromWorkWindow($dayStart, $dayEnd);
    }

    /**
     * @return list<array{date: string, start_time: string, end_time: string}>
     */
    private function splitDay(string $date, string $dayStart, string $dayEnd, int $durationMinutes): array
    {
        if ($dayStart >= $dayEnd) {
            return [];
        }

        $windows       = [];
        $cursorMinutes = self::toMinutes($dayStart);
        $endMinutes    = self::toMinutes($dayEnd);

        while ($cursorMinutes + $durationMinutes <= $endMinutes) {
            $start = self::fromMinutes($cursorMinutes);
            $end   = self::fromMinutes($cursorMinutes + $durationMinutes);
            BookableSlot::assertWindow($date, $start, $end);
            $windows[] = [
                'date'       => $date,
                'start_time' => $start,
                'end_time'   => $end,
            ];
            $cursorMinutes += $durationMinutes;
        }

        return $windows;
    }

    private static function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map(intval(...), explode(':', $hhmm, 2));

        return $h * 60 + $m;
    }

    private static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
