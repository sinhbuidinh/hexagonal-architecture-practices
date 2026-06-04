<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Doctor\Command;

use HexagonPractise\Application\Port\DoctorAppointmentSettingsCommandPort;
use HexagonPractise\Application\Port\DoctorCommandPort;
use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Shared\PractitionerId;

final readonly class CreateDoctor
{
    public function __construct(
        private DoctorCommandPort $doctors,
        private ?DoctorAppointmentSettingsCommandPort $appointmentSettings = null,
    ) {
    }

    /**
     * @param list<string> $specialties
     * @param list<string> $languages
     *
     * @return array{doctor_id: int, name: string, specialties: list<string>, languages: list<string>, license_number: string|null, accepting_new_patients: bool, user_id: int|null}
     */
    public function execute(
        int $doctorId,
        string $name,
        array $specialties = [],
        array $languages = [],
        ?string $licenseNumber = null,
        bool $acceptingNewPatients = true,
        ?int $userId = null,
    ): array {
        $doctor = new Doctor(
            id                   : new PractitionerId($doctorId),
            name                 : $name,
            specialties          : Doctor::normalizeStringList($specialties),
            languages            : Doctor::normalizeStringList($languages),
            licenseNumber        : $licenseNumber,
            acceptingNewPatients : $acceptingNewPatients,
            userId               : $userId,
        );
        $this->doctors->save($doctor);
        $this->appointmentSettings?->ensureDefaults($doctor->id);

        return $doctor->toArray();
    }
}
