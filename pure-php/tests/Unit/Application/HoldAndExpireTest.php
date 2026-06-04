<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Expiration\ProcessExpiredItems;
use HexagonPractise\Application\Patient\Command\CreatePatient;
use HexagonPractise\Application\Scheduling\Command\CancelAppointmentHold;
use HexagonPractise\Application\Scheduling\Command\HoldAppointment;
use HexagonPractise\Application\Scheduling\Command\PublishBookableSlots;
use HexagonPractise\Domain\Scheduling\BookableSlotUnavailableException;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPatientAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use PHPUnit\Framework\TestCase;

final class HoldAndExpireTest extends TestCase
{
    private InMemorySchedulingAdapter $scheduling;
    private InMemoryBookableSlotAdapter $bookableSlots;
    private InMemoryExpirationQueueAdapter $queue;
    private InMemoryDoctorAdapter $doctors;
    private InMemoryPatientAdapter $patients;
    private FrozenClock $clock;
    private HoldAppointment $holdAppointment;
    private CancelAppointmentHold $cancelAppointmentHold;

    protected function setUp(): void
    {
        $this->scheduling = new InMemorySchedulingAdapter();
        $this->bookableSlots = new InMemoryBookableSlotAdapter();
        $this->queue = new InMemoryExpirationQueueAdapter();
        $this->doctors = new InMemoryDoctorAdapter();
        $this->patients = new InMemoryPatientAdapter();
        $this->clock = new FrozenClock(new \DateTimeImmutable('2026-06-03T10:00:00Z'));

        (new CreateDoctor($this->doctors))->execute(1, 'Dr Smith');
        (new CreatePatient($this->patients))->execute('patient-42', 'Jane Doe');

        (new PublishBookableSlots($this->bookableSlots, $this->doctors))->execute(1, [
            ['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30'],
            ['date' => '2026-06-05', 'start_time' => '08:30', 'end_time' => '09:30'],
        ]);

        $this->holdAppointment = new HoldAppointment(
            $this->scheduling,
            $this->bookableSlots,
            $this->bookableSlots,
            $this->queue,
            $this->doctors,
            $this->patients,
        );
        $this->cancelAppointmentHold = new CancelAppointmentHold(
            $this->scheduling,
            $this->scheduling,
            $this->bookableSlots,
            $this->queue,
        );
    }

    public function testHoldMarksSlotUnavailable(): void
    {
        $this->holdAppointment->execute(
            'apt-1',
            1,
            'patient-42',
            1,
            $this->clock->now()->modify('+5 minutes'),
        );

        $slots = $this->bookableSlots->listAvailable(new \HexagonPractise\Domain\Shared\PractitionerId(1));
        $this->assertCount(1, $slots);
        $this->assertSame(2, $slots[0]->id->value);
    }

    public function testUnavailableSlotThrows(): void
    {
        $this->holdAppointment->execute(
            'apt-1',
            1,
            'patient-42',
            1,
            $this->clock->now()->modify('+5 minutes'),
        );

        $this->expectException(BookableSlotUnavailableException::class);

        $this->holdAppointment->execute(
            'apt-2',
            1,
            'patient-42',
            1,
            $this->clock->now()->modify('+5 minutes'),
        );
    }

    public function testExpiredHoldReleasesSlot(): void
    {
        $expiresAt = $this->clock->now()->modify('+1 minute');
        $this->holdAppointment->execute(
            'apt-expired',
            1,
            'patient-42',
            1,
            $expiresAt,
        );

        $this->clock->set($expiresAt->modify('+1 second'));

        $processor = new ProcessExpiredItems($this->queue, $this->cancelAppointmentHold, $this->clock);
        $processed = $processor->execute();

        $this->assertCount(1, $processed);
        $this->assertSame('cancelled_appointment_hold', $processed[0]['action']);

        $slots = $this->bookableSlots->listAvailable(new \HexagonPractise\Domain\Shared\PractitionerId(1));
        $this->assertCount(2, $slots);
    }
}
