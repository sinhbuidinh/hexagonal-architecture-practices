<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum ActorRole: string
{
    case Doctor     = 'doctor';
    case Pharmacist = 'pharmacist';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'doctor'     => self::Doctor,
            'pharmacist' => self::Pharmacist,
            default      => throw new \InvalidArgumentException(sprintf('Unknown actor role: %s', $value)),
        };
    }
}
