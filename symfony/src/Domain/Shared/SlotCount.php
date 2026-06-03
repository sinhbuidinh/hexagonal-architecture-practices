<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Number of bookable appointment slots for a practitioner. */
final readonly class SlotCount
{
    public function __construct(public int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('SlotCount cannot be negative.');
        }
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new \InvalidArgumentException('SlotCount underflow.');
        }

        return new self($result);
    }

    public function isGreaterOrEqual(self $other): bool
    {
        return $this->value >= $other->value;
    }
}
