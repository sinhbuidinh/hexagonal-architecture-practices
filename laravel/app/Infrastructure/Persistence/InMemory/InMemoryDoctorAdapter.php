<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\DoctorCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\Doctor;
use App\Domain\Shared\PractitionerId;

final class InMemoryDoctorAdapter implements DoctorCommandPort, DoctorQueryPort
{
    /** @var array<int, Doctor> */
    private array $store = [];

    private int $nextId = 1;

    public function create(
        string $name,
        ?int $userId = null,
        array $specialties = [],
        array $languages = [],
        ?string $licenseNumber = null,
        bool $acceptingNewPatients = true,
    ): Doctor {
        $doctor = new Doctor(
            id                  : new PractitionerId($this->nextId),
            name                : $name,
            specialties         : Doctor::normalizeStringList($specialties),
            languages           : Doctor::normalizeStringList($languages),
            licenseNumber       : $licenseNumber,
            acceptingNewPatients: $acceptingNewPatients,
            userId              : $userId,
        );
        $this->store[$doctor->id->value] = $doctor;
        ++$this->nextId;

        return $doctor;
    }

    public function save(Doctor $doctor): void
    {
        $this->store[$doctor->id->value] = $doctor;
    }

    public function find(PractitionerId $id): ?Doctor
    {
        return $this->store[$id->value] ?? null;
    }

    public function listAll(): array
    {
        return array_values($this->store);
    }
}
