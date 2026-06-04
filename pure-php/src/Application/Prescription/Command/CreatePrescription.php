<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Prescription\Command;

use HexagonPractise\Application\Port\PrescriptionCommandPort;
use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Prescription\PrescriptionStatus;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PrescriptionId;

final readonly class CreatePrescription
{
    public function __construct(private PrescriptionCommandPort $prescriptions)
    {
    }

    /** @return array<string, mixed> */
    public function execute(
        string $prescriptionId,
        string $patientId,
        string $medication,
        string $dosage,
        string $instructions = '',
    ): array {
        $prescription = new Prescription(
            id           : new PrescriptionId($prescriptionId),
            patientId    : new PatientId($patientId),
            medication   : $medication,
            dosage       : $dosage,
            instructions : $instructions,
            status       : PrescriptionStatus::DRAFT,
            pharmacyNotes: '',
            version      : 1,
        );

        $this->prescriptions->save($prescription);

        return $prescription->toArray();
    }
}
