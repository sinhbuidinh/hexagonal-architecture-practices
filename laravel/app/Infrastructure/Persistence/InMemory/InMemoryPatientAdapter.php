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

    private int $nextId = 1;

    public function create(
        string $name,
        ?int $userId = null,
        ?string $preferredLanguage = null,
        ?string $dateOfBirth = null,
        ?string $phone = null,
    ): Patient {
        $patient = new Patient(
            id               : new PatientId((string) $this->nextId),
            name             : $name,
            preferredLanguage: $preferredLanguage,
            dateOfBirth      : $dateOfBirth,
            phone            : $phone,
            userId           : $userId,
        );
        $this->store[$patient->id->value] = $patient;
        ++$this->nextId;

        return $patient;
    }

    public function save(Patient $patient): void
    {
        $this->store[$patient->id->value] = $patient;
    }

    public function find(PatientId $id): ?Patient
    {
        return $this->store[$id->value] ?? null;
    }
}
