<?php

declare(strict_types=1);

namespace App\Application\Prescription\Command;

use App\Application\Port\PrescriptionCommandPort;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionStatus;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PrescriptionId;

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
            new PrescriptionId($prescriptionId),
            new PatientId($patientId),
            $medication,
            $dosage,
            $instructions,
            PrescriptionStatus::Draft,
            '',
            1,
        );

        $this->prescriptions->save($prescription);

        return $prescription->toArray();
    }
}
