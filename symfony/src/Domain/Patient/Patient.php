<?php

declare(strict_types=1);

namespace App\Domain\Patient;

use App\Domain\Shared\PatientId;

final readonly class Patient
{
    /**
     * @param int|null $userId Optional portal account link; null for records without login (e.g. dependant)
     */
    public function __construct(
        public PatientId $id,
        public string $name,
        public ?string $preferredLanguage = null,
        public ?string $dateOfBirth = null,
        public ?string $phone = null,
        public ?int $userId = null,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Patient name cannot be empty.');
        }

        if ($dateOfBirth !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
            throw new \InvalidArgumentException('Patient date of birth must be YYYY-MM-DD.');
        }
    }

    /** @return array{patient_id: string, name: string, preferred_language: string|null, date_of_birth: string|null, phone: string|null, user_id: int|null} */
    public function toArray(): array
    {
        return [
            'patient_id'         => $this->id->value,
            'name'               => $this->name,
            'preferred_language' => $this->preferredLanguage,
            'date_of_birth'      => $this->dateOfBirth,
            'phone'              => $this->phone,
            'user_id'            => $this->userId,
        ];
    }
}
