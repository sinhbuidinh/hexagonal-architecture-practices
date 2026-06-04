<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Prescription;

enum PrescriptionStatus: string
{
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case DISPENSED = 'dispensed';

    public static function fromString(string $value): self
    {
        return self::from(strtolower($value));
    }
}
