<?php

declare(strict_types=1);

namespace App\Application\Prescription\Command;

use App\Application\Port\PrescriptionCommandPort;
use App\Domain\Shared\PatientId;

final readonly class CreatePrescription
{
    public function __construct(private PrescriptionCommandPort $prescriptions)
    {
    }

    /** @return array<string, mixed> */
    public function execute(
        string $patientId,
        string $medication,
        string $dosage,
        string $instructions = '',
    ): array {
        $prescription = $this->prescriptions->create(
            patientId   : new PatientId($patientId),
            medication  : $medication,
            dosage      : $dosage,
            instructions: $instructions,
        );

        return $prescription->toArray();
    }
}
