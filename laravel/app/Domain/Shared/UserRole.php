<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum UserRole: string
{
    case DOCTOR     = 'doctor';
    case PHARMACIST = 'pharmacist';
    case PATIENT    = 'patient';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'doctor'     => self::DOCTOR,
            'pharmacist' => self::PHARMACIST,
            'patient'    => self::PATIENT,
            default      => throw new \InvalidArgumentException(sprintf('Unknown user role: %s', $value)),
        };
    }

    public function toActorRole(): ActorRole
    {
        return match ($this) {
            self::DOCTOR     => ActorRole::DOCTOR,
            self::PHARMACIST => ActorRole::PHARMACIST,
            self::PATIENT    => throw new \InvalidArgumentException('Patient role cannot act on prescriptions.'),
        };
    }
}
