<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Domain;

use HexagonPractise\Domain\Shared\SlotCount;
use PHPUnit\Framework\TestCase;

final class SlotCountTest extends TestCase
{
    public function testSubtractThrowsOnUnderflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new SlotCount(1))->subtract(new SlotCount(2));
    }

    public function testIsGreaterOrEqual(): void
    {
        $this->assertTrue((new SlotCount(5))->isGreaterOrEqual(new SlotCount(3)));
        $this->assertTrue((new SlotCount(3))->isGreaterOrEqual(new SlotCount(3)));
        $this->assertFalse((new SlotCount(2))->isGreaterOrEqual(new SlotCount(3)));
    }
}
