<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Patient\Command;

use HexagonPractise\Application\Port\PatientCommandPort;
use HexagonPractise\Domain\Patient\Patient;
use HexagonPractise\Domain\Shared\PatientId;

final readonly class CreatePatient
{
    public function __construct(private PatientCommandPort $patients)
    {
    }

    /**
     * @return array{patient_id: string, name: string, preferred_language: string|null, date_of_birth: string|null, phone: string|null, user_id: int|null}
     */
    public function execute(
        string $patientId,
        string $name,
        ?string $preferredLanguage = null,
        ?string $dateOfBirth = null,
        ?string $phone = null,
        ?int $userId = null,
    ): array {
        $patient = new Patient(
            id                : new PatientId($patientId),
            name              : $name,
            preferredLanguage : $preferredLanguage,
            dateOfBirth       : $dateOfBirth,
            phone             : $phone,
            userId            : $userId,
        );
        $this->patients->save($patient);

        return $patient->toArray();
    }
}
