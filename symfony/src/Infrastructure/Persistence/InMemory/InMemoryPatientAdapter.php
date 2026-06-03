<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\PatientCommandPort;
use App\Application\Port\PatientQueryPort;
use App\Domain\Patient\Patient;
use App\Domain\Shared\PatientId;

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
