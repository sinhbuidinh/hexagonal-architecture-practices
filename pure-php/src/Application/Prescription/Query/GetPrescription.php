<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Prescription\Query;

use HexagonPractise\Application\Port\PrescriptionQueryPort;
use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Prescription\PrescriptionNotFoundException;
use HexagonPractise\Domain\Shared\PrescriptionId;

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
        $id           = new PrescriptionId($prescriptionId);
        $prescription = $this->prescriptions->find($id);
        if ($prescription === null) {
            throw new PrescriptionNotFoundException($id);
        }

        return $prescription;
    }
}
