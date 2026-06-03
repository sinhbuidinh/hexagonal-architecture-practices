<?php

declare(strict_types=1);

namespace App\Domain\Prescription;

enum PrescriptionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Dispensed = 'dispensed';

    public static function fromString(string $value): self
    {
        return self::from(strtolower($value));
    }
}
