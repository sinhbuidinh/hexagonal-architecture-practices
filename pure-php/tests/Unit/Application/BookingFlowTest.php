<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Booking\Query\ListBookableAppointments;
use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\SetPractitionerAvailability;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use PHPUnit\Framework\TestCase;

final class BookingFlowTest extends TestCase
{
    public function testListBookableShowsOnlyDoctorsWithOpenSlots(): void
    {
        $doctors    = new InMemoryDoctorAdapter();
        $scheduling = new InMemorySchedulingAdapter();

        (new CreateDoctor($doctors))->execute('dr-a', 'Dr Alpha');
        (new CreateDoctor($doctors))->execute('dr-b', 'Dr Beta');
        (new CreateDoctor($doctors))->execute('dr-c', 'Dr Gamma');

        (new SetPractitionerAvailability($scheduling, $doctors))->execute('dr-a', 3);
        (new SetPractitionerAvailability($scheduling, $doctors))->execute('dr-b', 0);
        (new SetPractitionerAvailability($scheduling, $doctors))->execute('dr-c', 1);

        $list       = (new ListBookableAppointments($doctors, $scheduling))->execute();

        $this->assertCount(2, $list);
        $this->assertSame([
            ['doctor_id' => 'dr-a', 'name' => 'Dr Alpha', 'available_slots' => 3],
            ['doctor_id' => 'dr-c', 'name' => 'Dr Gamma', 'available_slots' => 1],
        ], $list);
    }
}
