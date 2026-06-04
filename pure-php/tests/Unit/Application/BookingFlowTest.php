<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Booking\Query\ListBookableAppointments;
use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\PublishBookableSlots;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use PHPUnit\Framework\TestCase;

final class BookingFlowTest extends TestCase
{
    public function testListBookableReturnsTimeWindowsForDoctor(): void
    {
        $doctors       = new InMemoryDoctorAdapter();
        $bookableSlots = new InMemoryBookableSlotAdapter();

        (new CreateDoctor($doctors))->execute(1, 'Dr Alpha');
        (new CreateDoctor($doctors))->execute(2, 'Dr Beta');

        (new PublishBookableSlots($bookableSlots, $doctors))->execute(1, [
            ['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30'],
            ['date' => '2026-06-05', 'start_time' => '08:30', 'end_time' => '09:30'],
        ]);
        (new PublishBookableSlots($bookableSlots, $doctors))->execute(2, [
            ['date' => '2026-06-06', 'start_time' => '10:00', 'end_time' => '11:00'],
        ]);

        $list = (new ListBookableAppointments($doctors, $bookableSlots))->execute(1);

        $this->assertCount(2, $list);
        $this->assertSame(
            expected: [
                [
                    'slot_id'    => 1,
                    'doctor_id'  => 1,
                    'date'       => '2026-06-05',
                    'start_time' => '07:30',
                    'end_time'   => '08:30',
                ],
                [
                    'slot_id'    => 2,
                    'doctor_id'  => 1,
                    'date'       => '2026-06-05',
                    'start_time' => '08:30',
                    'end_time'   => '09:30',
                ],
            ],
            actual  : $list,
        );
    }

    public function testListBookableFiltersByDateRange(): void
    {
        $doctors       = new InMemoryDoctorAdapter();
        $bookableSlots = new InMemoryBookableSlotAdapter();

        (new CreateDoctor($doctors))->execute(1, 'Dr Alpha');
        (new PublishBookableSlots($bookableSlots, $doctors))->execute(1, [
            ['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30'],
            ['date' => '2026-06-08', 'start_time' => '09:00', 'end_time' => '10:00'],
        ]);

        $list = (new ListBookableAppointments($doctors, $bookableSlots))->execute(1, '2026-06-05', '2026-06-06');

        $this->assertCount(1, $list);
        $this->assertSame('2026-06-05', $list[0]['date']);
    }
}
