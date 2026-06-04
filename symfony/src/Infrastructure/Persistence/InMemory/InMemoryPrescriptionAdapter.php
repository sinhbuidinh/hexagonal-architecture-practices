<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\PrescriptionCommandPort;
use App\Application\Port\PrescriptionQueryPort;
use App\Domain\Prescription\ConcurrentUpdateException;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use App\Domain\Shared\PrescriptionId;

final class InMemoryPrescriptionAdapter implements PrescriptionCommandPort, PrescriptionQueryPort
{
    /** @var array<string, Prescription> */
    private array $store = [];

    public function save(Prescription $prescription): void
    {
        $this->store[$prescription->id->value] = $prescription;
    }

    public function find(PrescriptionId $id): ?Prescription
    {
        return $this->store[$id->value] ?? null;
    }

    public function updateIfVersionMatches(Prescription $prescription, int $expectedVersion): Prescription
    {
        $current = $this->find($prescription->id);
        if ($current === null) {
            throw new PrescriptionNotFoundException($prescription->id);
        }

        if ($current->version !== $expectedVersion) {
            throw new ConcurrentUpdateException(
                prescriptionId : $prescription->id,
                expectedVersion: $expectedVersion,
                currentVersion : $current->version,
            );
        }

        $saved = new Prescription(
            id           : $prescription->id,
            patientId    : $prescription->patientId,
            medication   : $prescription->medication,
            dosage       : $prescription->dosage,
            instructions : $prescription->instructions,
            status       : $prescription->status,
            pharmacyNotes: $prescription->pharmacyNotes,
            version      : $expectedVersion + 1,
            lastUpdatedBy: $prescription->lastUpdatedBy,
        );

        $this->store[$prescription->id->value] = $saved;

        return $saved;
    }
}
