<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\InMemory;

use HexagonPractise\Application\Port\PatientCommandPort;
use HexagonPractise\Application\Port\PatientQueryPort;
use HexagonPractise\Domain\Patient\Patient;
use HexagonPractise\Domain\Shared\PatientId;

final class InMemoryPatientAdapter implements PatientCommandPort, PatientQueryPort
{
    /** @var array<string, Patient> */
    private array $store = [];

    public function save(Patient $patient): void
    {
        $this->store[$patient->id->value] = $patient;
    }

    public function find(PatientId $id): ?Patient
    {
        return $this->store[$id->value] ?? null;
    }
}
