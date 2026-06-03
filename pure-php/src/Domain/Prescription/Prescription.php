<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Prescription;

use HexagonPractise\Domain\Shared\ActorRole;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PrescriptionId;

/** Aggregate root; {@see $version} enables optimistic concurrency control. */
final readonly class Prescription
{
    public function __construct(
        public PrescriptionId $id,
        public PatientId $patientId,
        public string $medication,
        public string $dosage,
        public string $instructions,
        public PrescriptionStatus $status,
        public string $pharmacyNotes,
        public int $version,
        public ?ActorRole $lastUpdatedBy = null,
    ) {
        if ($version < 1) {
            throw new \InvalidArgumentException('Prescription version must be >= 1.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'prescription_id' => $this->id->value,
            'patient_id'      => $this->patientId->value,
            'medication'      => $this->medication,
            'dosage'          => $this->dosage,
            'instructions'    => $this->instructions,
            'status'          => $this->status->value,
            'pharmacy_notes'  => $this->pharmacyNotes,
            'version'         => $this->version,
            'last_updated_by' => $this->lastUpdatedBy?->value,
        ];
    }
}
