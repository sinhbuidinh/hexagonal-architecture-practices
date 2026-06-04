<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\InMemory;

use HexagonPractise\Application\Port\DoctorCommandPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Shared\PractitionerId;

final class InMemoryDoctorAdapter implements DoctorCommandPort, DoctorQueryPort
{
    /** @var array<int, Doctor> */
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
