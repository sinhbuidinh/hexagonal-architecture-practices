<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Patient;

use HexagonPractise\Domain\Shared\PatientId;

final readonly class Patient
{
    public function __construct(
        public PatientId $id,
        public string $name,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Patient name cannot be empty.');
        }
    }

    /** @return array{patient_id: string, name: string} */
    public function toArray(): array
    {
        return [
            'patient_id' => $this->id->value,
            'name'       => $this->name,
        ];
    }
}
