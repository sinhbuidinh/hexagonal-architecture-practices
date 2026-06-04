<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Shared;

final readonly class BookableSlotId
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('BookableSlotId must be a positive integer.');
        }
    }
}
