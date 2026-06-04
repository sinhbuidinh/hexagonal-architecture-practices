<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Expiration\ProcessExpiredItems;
use HexagonPractise\Application\Patient\Command\CreatePatient;
use HexagonPractise\Application\Scheduling\Command\CancelAppointmentHold;
use HexagonPractise\Application\Scheduling\Command\HoldAppointment;
use HexagonPractise\Application\Scheduling\Command\SetPractitionerAvailability;
use HexagonPractise\Domain\Scheduling\NoSlotsAvailableException;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPatientAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use PHPUnit\Framework\TestCase;

final class HoldAndExpireTest extends TestCase
{
    private InMemorySchedulingAdapter $scheduling;
    private InMemoryExpirationQueueAdapter $queue;
    private InMemoryDoctorAdapter $doctors;
    private InMemoryPatientAdapter $patients;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->scheduling = new InMemorySchedulingAdapter();
        $this->queue = new InMemoryExpirationQueueAdapter();
        $this->doctors = new InMemoryDoctorAdapter();
        $this->patients = new InMemoryPatientAdapter();
        $this->clock = new FrozenClock(new \DateTimeImmutable('2026-06-03T10:00:00Z'));

        (new CreateDoctor($this->doctors))->execute('dr-smith', 'Dr Smith');
        (new CreatePatient($this->patients))->execute('patient-42', 'Jane Doe');
    }

    public function testHoldReducesAvailableSlots(): void
    {
        (new SetPractitionerAvailability($this->scheduling, $this->doctors))->execute('dr-smith', 10);

        (new HoldAppointment($this->scheduling, $this->queue, $this->doctors, $this->patients))->execute(
            'apt-1',
            'dr-smith',
            'patient-42',
            4,
            $this->clock->now()->modify('+5 minutes'),
        );

        $this->assertSame(6, $this->scheduling->availableSlots(new PractitionerId('dr-smith'))->value);
    }

    public function testNoSlotsAvailableThrows(): void
    {
        (new SetPractitionerAvailability($this->scheduling, $this->doctors))->execute('dr-smith', 2);

        $this->expectException(NoSlotsAvailableException::class);

        (new HoldAppointment($this->scheduling, $this->queue, $this->doctors, $this->patients))->execute(
            'apt-1',
            'dr-smith',
            'patient-42',
            5,
            $this->clock->now()->modify('+5 minutes'),
        );
    }

    public function testExpiredHoldReturnsSlots(): void
    {
        (new SetPractitionerAvailability($this->scheduling, $this->doctors))->execute('dr-smith', 10);

        $expiresAt = $this->clock->now()->modify('+1 minute');
        (new HoldAppointment($this->scheduling, $this->queue, $this->doctors, $this->patients))->execute(
            'apt-expired',
            'dr-smith',
            'patient-42',
            3,
            $expiresAt,
        );

        $this->clock->set($expiresAt->modify('+1 second'));

        $cancel    = new CancelAppointmentHold($this->scheduling, $this->queue);
        $processor = new ProcessExpiredItems($this->queue, $cancel, $this->clock);
        $processed = $processor->execute();

        $this->assertCount(1, $processed);
        $this->assertSame('cancelled_appointment_hold', $processed[0]['action']);
        $this->assertSame(10, $this->scheduling->availableSlots(new PractitionerId('dr-smith'))->value);
    }
}
