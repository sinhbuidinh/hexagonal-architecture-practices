<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum UserRole: string
{
    case ADMIN        = 'admin';
    case DOCTOR       = 'doctor';
    case RECEPTIONIST = 'receptionist';
    case PHARMACIST   = 'pharmacist';
    case PATIENT      = 'patient';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'admin'        => self::ADMIN,
            'doctor'       => self::DOCTOR,
            'receptionist' => self::RECEPTIONIST,
            'pharmacist'   => self::PHARMACIST,
            'patient'      => self::PATIENT,
            default        => throw new \InvalidArgumentException(sprintf('Unknown user role: %s', $value)),
        };
    }

    public function toActorRole(): ActorRole
    {
        return match ($this) {
            self::DOCTOR                                   => ActorRole::DOCTOR,
            self::PHARMACIST                               => ActorRole::PHARMACIST,
            self::ADMIN, self::RECEPTIONIST, self::PATIENT => throw new \InvalidArgumentException(
                sprintf('Role "%s" has no clinical actor mapping.', $this->value),
            ),
        };
    }
}
