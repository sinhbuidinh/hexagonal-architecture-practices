<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Doctor\Doctor;

interface DoctorCommandPort
{
    /**
     * @param list<string> $specialties
     * @param list<string> $languages
     */
    public function create(
        string $name,
        ?int $userId = null,
        array $specialties = [],
        array $languages = [],
        ?string $licenseNumber = null,
        bool $acceptingNewPatients = true,
    ): Doctor;

    public function save(Doctor $doctor): void;
}
