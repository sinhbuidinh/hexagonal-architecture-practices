<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\DoctorCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\Doctor;
use App\Domain\Shared\PractitionerId;

final class InMemoryDoctorAdapter implements DoctorCommandPort, DoctorQueryPort
{
    /** @var array<string, Doctor> */
    private array $store = [];

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
