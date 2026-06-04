<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Domain;

use HexagonPractise\Domain\Scheduling\BookableSlot;
use HexagonPractise\Domain\Scheduling\OverlappingBookableWindowException;
use PHPUnit\Framework\TestCase;

final class BookableSlotOverlapTest extends TestCase
{
    public function testAdjacentWindowsDoNotOverlap(): void
    {
        $this->assertFalse(BookableSlot::windowsOverlapOnDate(
            '2026-06-05',
            '07:30',
            '08:30',
            '2026-06-05',
            '08:30',
            '09:30',
        ));
    }

    public function testIntersectingWindowsOverlap(): void
    {
        $this->assertTrue(BookableSlot::windowsOverlapOnDate(
            '2026-06-05',
            '07:30',
            '08:30',
            '2026-06-05',
            '08:00',
            '09:00',
        ));
    }

    public function testBatchRejectsInternalOverlap(): void
    {
        $this->expectException(OverlappingBookableWindowException::class);

        BookableSlot::assertNoOverlapWithinBatch([
            ['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30'],
            ['date' => '2026-06-05', 'start_time' => '08:00', 'end_time' => '09:00'],
        ]);
    }

    public function testRejectsOverlapWithExisting(): void
    {
        $this->expectException(OverlappingBookableWindowException::class);

        BookableSlot::assertNoOverlapWithExisting(
            [['date' => '2026-06-05', 'start_time' => '08:00', 'end_time' => '09:00']],
            [['date' => '2026-06-05', 'start_time' => '07:30', 'end_time' => '08:30']],
        );
    }
}
