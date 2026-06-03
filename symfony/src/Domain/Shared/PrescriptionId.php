<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final readonly class PrescriptionId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('PrescriptionId cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
