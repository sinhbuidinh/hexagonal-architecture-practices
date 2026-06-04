<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Doctor;

use HexagonPractise\Domain\Shared\PractitionerId;

/** Rules for rolling bookable-slot materialization (weekly hours + slot length). */
final readonly class DoctorAppointmentSettings
{
    public const int DEFAULT_SLOT_DURATION_MINUTES = 15;

    public const string DEFAULT_TIMEZONE = 'UTC';

    /** @var list<string> */
    public const array WEEKDAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /**
     * @param array<string, DailyWorkHours|null> $weeklySchedule keys: mon..sun; null = day off
     */
    public function __construct(
        public PractitionerId $practitionerId,
        public int $slotDurationMinutes,
        public array $weeklySchedule,
        public string $timezone = self::DEFAULT_TIMEZONE,
    ) {
        if ($this->slotDurationMinutes < 5 || $this->slotDurationMinutes > 240) {
            throw new \InvalidArgumentException('slot_duration_minutes must be between 5 and 240.');
        }

        foreach (self::WEEKDAY_KEYS as $key) {
            if (!array_key_exists($key, $this->weeklySchedule)) {
                throw new \InvalidArgumentException(sprintf('weekly_schedule must include "%s".', $key));
            }
        }
    }

    public static function defaultFor(PractitionerId $practitionerId): self
    {
        $weekday = static fn (): DailyWorkHours => new DailyWorkHours('08:00', '17:00');

        return new self(
            practitionerId     : $practitionerId,
            slotDurationMinutes: self::DEFAULT_SLOT_DURATION_MINUTES,
            weeklySchedule     : [
                'mon' => $weekday(),
                'tue' => $weekday(),
                'wed' => $weekday(),
                'thu' => $weekday(),
                'fri' => $weekday(),
                'sat' => null,
                'sun' => null,
            ],
            timezone: self::DEFAULT_TIMEZONE,
        );
    }

    public function dayScheduleFor(\DateTimeImmutable $localDate): ?DailyWorkHours
    {
        $key = strtolower(substr($localDate->format('D'), 0, 3));

        return $this->weeklySchedule[$key] ?? null;
    }

    /**
     * @param array<string, mixed> $weeklyInput e.g. mon => {start_time, end_time} or null
     *
     * @return array<string, DailyWorkHours|null>
     */
    public static function weeklyScheduleFromArray(array $weeklyInput): array
    {
        $schedule = [];
        foreach (self::WEEKDAY_KEYS as $key) {
            $day = $weeklyInput[$key] ?? null;
            if ($day === null || $day === []) {
                $schedule[$key] = null;
                continue;
            }

            if (!is_array($day)) {
                throw new \InvalidArgumentException(sprintf('weekly_schedule.%s must be an object or null.', $key));
            }

            $schedule[$key] = new DailyWorkHours(
                startTime: (string) ($day['start_time'] ?? $day['start'] ?? ''),
                endTime  : (string) ($day['end_time'] ?? $day['end'] ?? ''),
            );
        }

        return $schedule;
    }

    /** @return array{practitioner_id: int, slot_duration_minutes: int, weekly_schedule: array<string, array{start_time: string, end_time: string}|null>, timezone: string} */
    public function toArray(): array
    {
        $weekly = [];
        foreach (self::WEEKDAY_KEYS as $key) {
            $hours = $this->weeklySchedule[$key];
            $weekly[$key] = $hours === null
                ? null
                : ['start_time' => $hours->startTime, 'end_time' => $hours->endTime];
        }

        return [
            'practitioner_id'        => $this->practitionerId->value,
            'slot_duration_minutes'  => $this->slotDurationMinutes,
            'weekly_schedule'        => $weekly,
            'timezone'               => $this->timezone,
        ];
    }
}
