<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Doctor;

use HexagonPractise\Domain\Shared\PractitionerId;

/** Registered clinician; {@see PractitionerId} is the scheduling resource id. */
final readonly class Doctor
{
    public function __construct(
        public PractitionerId $id,
        public string $name,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Doctor name cannot be empty.');
        }
    }

    /** @return array{doctor_id: string, name: string} */
    public function toArray(): array
    {
        return [
            'doctor_id' => $this->id->value,
            'name'      => $this->name,
        ];
    }
}
