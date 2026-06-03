<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Shared;

final readonly class AppointmentId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('AppointmentId cannot be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
