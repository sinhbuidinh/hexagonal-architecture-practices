<?php

declare(strict_types=1);

namespace App\Application\Prescription\Query;

use App\Application\Port\PrescriptionQueryPort;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use App\Domain\Shared\PrescriptionId;

final readonly class GetPrescription
{
    public function __construct(private PrescriptionQueryPort $prescriptions)
    {
    }

    /** @return array<string, mixed> */
    public function execute(string $prescriptionId): array
    {
        return $this->find($prescriptionId)->toArray();
    }

    public function find(string $prescriptionId): Prescription
    {
        $id = new PrescriptionId($prescriptionId);
        $prescription = $this->prescriptions->find($id);
        if ($prescription === null) {
            throw new PrescriptionNotFoundException($id);
        }

        return $prescription;
    }
}
