<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Patient\Patient;

interface PatientCommandPort
{
    public function create(
        string $name,
        ?int $userId = null,
        ?string $preferredLanguage = null,
        ?string $dateOfBirth = null,
        ?string $phone = null,
    ): Patient;

    public function save(Patient $patient): void;
}
