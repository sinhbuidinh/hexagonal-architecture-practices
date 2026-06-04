<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\MaterializeBookableSlots;
use HexagonPractise\Domain\Doctor\DoctorAppointmentSettings;
use HexagonPractise\Domain\Scheduling\BookableSlotGenerator;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAppointmentSettingsAdapter;
use HexagonPractise\Infrastructure\Scheduling\ClinicLunchBreakFromConfig;
use HexagonPractise\Infrastructure\Scheduling\FixedBookableSlotHorizon;
use PHPUnit\Framework\TestCase;

final class MaterializeBookableSlotsLunchBreakTest extends TestCase
{
    private const string MONDAY = '2026-06-02';

    /** Same keys as pure-php/config/app.php and Laravel CLINIC_LUNCH_BREAK_* env. */
    private const array LUNCH_BREAK_CONFIG = [
        'clinic_lunch_break_enabled' => true,
        'clinic_lunch_break_start'   => '12:00',
        'clinic_lunch_break_end'     => '13:30',
    ];

    public function testMaterializedSlotsExcludeConfiguredLunchBreak(): void
    {
        $fixture = $this->fixture();
        $clock   = new FrozenClock(new \DateTimeImmutable(self::MONDAY . ' 08:00:00', new \DateTimeZone('UTC')));

        $generator = new BookableSlotGenerator(
            ClinicLunchBreakFromConfig::fromConfigArray(self::LUNCH_BREAK_CONFIG)->lunchBreak(),
        );

        (new MaterializeBookableSlots(
            $fixture['bookableSlots'],
            $fixture['settingsStore'],
            $fixture['doctors'],
            new FixedBookableSlotHorizon(1),
            $clock,
            $generator,
        ))->execute($fixture['practitionerId']);

        $startTimes = $this->startTimesOnMonday($fixture['bookableSlots'], $fixture['practitionerId']);

        $this->assertSame(['09:00', '10:00', '11:00', '13:30', '14:30'], $startTimes);

        foreach ($startTimes as $start) {
            $this->assertFalse(self::overlapsLunchBreak($start, self::addMinutes($start, 60)));
        }
    }

    public function testMaterializedSlotsIncludeLunchWindowWhenBreakDisabledInConfig(): void
    {
        $fixture = $this->fixture();
        $clock   = new FrozenClock(new \DateTimeImmutable(self::MONDAY . ' 08:00:00', new \DateTimeZone('UTC')));

        $config = self::LUNCH_BREAK_CONFIG;
        $config['clinic_lunch_break_enabled'] = false;

        $generator = new BookableSlotGenerator(
            ClinicLunchBreakFromConfig::fromConfigArray($config)->lunchBreak(),
        );

        (new MaterializeBookableSlots(
            $fixture['bookableSlots'],
            $fixture['settingsStore'],
            $fixture['doctors'],
            new FixedBookableSlotHorizon(1),
            $clock,
            $generator,
        ))->execute($fixture['practitionerId']);

        $startTimes = $this->startTimesOnMonday($fixture['bookableSlots'], $fixture['practitionerId']);

        $this->assertContains('12:00', $startTimes);
    }

    /**
     * @return array{
     *     practitionerId: int,
     *     doctors: InMemoryDoctorAdapter,
     *     settingsStore: InMemoryDoctorAppointmentSettingsAdapter,
     *     bookableSlots: InMemoryBookableSlotAdapter
     * }
     */
    private function fixture(): array
    {
        $doctors       = new InMemoryDoctorAdapter();
        $settingsStore = new InMemoryDoctorAppointmentSettingsAdapter();
        $bookableSlots = new InMemoryBookableSlotAdapter();

        $doctor = (new CreateDoctor($doctors, $settingsStore))->execute(1, 'Dr All-day');
        $id     = new PractitionerId($doctor['doctor_id']);

        $allDay = static fn (): array => ['start_time' => '09:00', 'end_time' => '15:30'];

        $settingsStore->save(new DoctorAppointmentSettings(
            practitionerId     : $id,
            slotDurationMinutes: 60,
            weeklySchedule     : DoctorAppointmentSettings::weeklyScheduleFromArray([
                'mon' => $allDay(),
                'tue' => $allDay(),
                'wed' => $allDay(),
                'thu' => $allDay(),
                'fri' => $allDay(),
                'sat' => null,
                'sun' => null,
            ]),
            timezone: 'UTC',
        ));

        return [
            'practitionerId' => $id->value,
            'doctors'        => $doctors,
            'settingsStore'  => $settingsStore,
            'bookableSlots'  => $bookableSlots,
        ];
    }

    /** @return list<string> */
    private function startTimesOnMonday(InMemoryBookableSlotAdapter $bookableSlots, int $practitionerId): array
    {
        $slots = $bookableSlots->listAvailable(new PractitionerId($practitionerId));

        $monday = array_values(array_filter(
            $slots,
            static fn ($slot): bool => $slot->date === self::MONDAY,
        ));

        usort(
            $monday,
            static fn ($a, $b): int => $a->startTime <=> $b->startTime,
        );

        return array_map(static fn ($slot): string => $slot->startTime, $monday);
    }

    private static function overlapsLunchBreak(string $start, string $end): bool
    {
        return $start < '13:30' && '12:00' < $end;
    }

    private static function addMinutes(string $hhmm, int $minutes): string
    {
        [$h, $m] = array_map(intval(...), explode(':', $hhmm, 2));
        $total = $h * 60 + $m + $minutes;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
