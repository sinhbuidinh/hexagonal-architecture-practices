<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\PublishBookableSlots;
use HexagonPractise\Domain\Scheduling\OverlappingBookableWindowException;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use PHPUnit\Framework\TestCase;

final class PublishBookableSlotsOverlapTest extends TestCase
{
    public function testPublishRejectsOverlapWithExistingSlot(): void
    {
        $doctors       = new InMemoryDoctorAdapter();
        $bookableSlots = new InMemoryBookableSlotAdapter();

        (new CreateDoctor($doctors))->execute(1, 'Dr Alpha');
        (new PublishBookableSlots($bookableSlots, $doctors))->execute(1, [
            ['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30'],
        ]);

        $this->expectException(OverlappingBookableWindowException::class);

        (new PublishBookableSlots($bookableSlots, $doctors))->execute(1, [
            ['date' => '2026-06-05', 'start_time' => '08:00', 'end_time' => '09:00'],
        ]);
    }
}
