<?php

declare(strict_types=1);

namespace App\Application\Patient\Command;

use App\Application\Port\PatientCommandPort;
use App\Domain\Patient\Patient;
use App\Domain\Shared\PatientId;

final readonly class CreatePatient
{
    public function __construct(private PatientCommandPort $patients)
    {
    }

    /** @return array{patient_id: string, name: string} */
    public function execute(string $patientId, string $name): array
    {
        $patient = new Patient(new PatientId($patientId), $name);
        $this->patients->save($patient);

        return $patient->toArray();
    }
}
