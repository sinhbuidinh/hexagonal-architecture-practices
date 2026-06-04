<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum ActorRole: string
{
    case DOCTOR     = 'doctor';
    case PHARMACIST = 'pharmacist';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'doctor'     => self::DOCTOR,
            'pharmacist' => self::PHARMACIST,
            default      => throw new \InvalidArgumentException(sprintf('Unknown actor role: %s', $value)),
        };
    }
}
