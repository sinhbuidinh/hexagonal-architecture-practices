<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\MaterializeBookableSlots;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAppointmentSettingsAdapter;
use HexagonPractise\Infrastructure\Scheduling\FixedBookableSlotHorizon;
use PHPUnit\Framework\TestCase;

final class MaterializeBookableSlotsTest extends TestCase
{
    public function testMaterializeInsertsFutureAvailableSlots(): void
    {
        $doctors       = new InMemoryDoctorAdapter();
        $settingsStore = new InMemoryDoctorAppointmentSettingsAdapter();
        $bookableSlots = new InMemoryBookableSlotAdapter();
        $clock         = new FrozenClock(new \DateTimeImmutable('2026-06-04 13:56:00', new \DateTimeZone('UTC')));

        (new CreateDoctor($doctors, $settingsStore))->execute(1, 'Dr Alpha');
        $settingsStore->ensureDefaults(new \HexagonPractise\Domain\Shared\PractitionerId(1));

        $result = (new MaterializeBookableSlots(
            $bookableSlots,
            $settingsStore,
            $doctors,
            new FixedBookableSlotHorizon(15),
            $clock,
        ))->execute(1);

        $this->assertGreaterThan(0, $result['inserted']);
        $this->assertGreaterThan(0, $result['generated']);

        $available = $bookableSlots->listAvailable(new \HexagonPractise\Domain\Shared\PractitionerId(1));
        $this->assertNotEmpty($available);
        $this->assertSame('14:00', $available[0]->startTime);
    }
}
