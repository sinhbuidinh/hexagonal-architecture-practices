<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Domain;

use HexagonPractise\Domain\Doctor\DailyWorkHours;
use HexagonPractise\Domain\Doctor\DoctorAppointmentSettings;
use HexagonPractise\Domain\Scheduling\BookableSlotGenerator;
use HexagonPractise\Domain\Scheduling\ClinicLunchBreak;
use HexagonPractise\Domain\Shared\PractitionerId;
use PHPUnit\Framework\TestCase;

final class BookableSlotGeneratorTest extends TestCase
{
    public function testCeilsCurrentTimeToNextSlotBoundary(): void
    {
        $now = new \DateTimeImmutable('2026-06-04 13:56:00', new \DateTimeZone('UTC'));

        $this->assertSame('14:00', BookableSlotGenerator::ceilToSlotStart($now, 15));
    }

    public function testGenerateStartsTodayAfterCeiledTime(): void
    {
        $settings  = DoctorAppointmentSettings::defaultFor(new PractitionerId(1));
        $generator = new BookableSlotGenerator();
        $now       = new \DateTimeImmutable('2026-06-04 13:56:00', new \DateTimeZone('UTC'));

        $windows = $generator->generate($settings, $now, 15);

        $this->assertNotEmpty($windows);
        $this->assertSame('2026-06-04', $windows[0]['date']);
        $this->assertSame('14:00', $windows[0]['start_time']);
        $this->assertSame('14:15', $windows[0]['end_time']);

        $last = $windows[array_key_last($windows)];
        $this->assertSame('2026-06-18', $last['date']);
    }

    public function testGenerateExcludesGlobalLunchBreak(): void
    {
        $allDay   = static fn (): DailyWorkHours => new DailyWorkHours('09:00', '15:30');
        $settings = new DoctorAppointmentSettings(
            practitionerId     : new PractitionerId(1),
            slotDurationMinutes: 60,
            weeklySchedule     : [
                'mon' => $allDay(),
                'tue' => $allDay(),
                'wed' => $allDay(),
                'thu' => $allDay(),
                'fri' => $allDay(),
                'sat' => null,
                'sun' => null,
            ],
            timezone           : 'UTC',
        );

        $generator = new BookableSlotGenerator(new ClinicLunchBreak('12:00', '13:30'));
        $now       = new \DateTimeImmutable('2026-06-02 08:00:00', new \DateTimeZone('UTC'));
        $windows   = $generator->generate($settings, $now, 1);

        $this->assertSame(
            ['09:00', '10:00', '11:00', '13:30', '14:30'],
            array_map(static fn (array $window): string => $window['start_time'], $windows),
        );

        foreach ($windows as $window) {
            $this->assertFalse(
                BookableSlotGeneratorTest::windowOverlapsLunch($window['start_time'], $window['end_time']),
            );
        }
    }

    private static function windowOverlapsLunch(string $start, string $end): bool
    {
        return $start < '13:30' && '12:00' < $end;
    }
}
