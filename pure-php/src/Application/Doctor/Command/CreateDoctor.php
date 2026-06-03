<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Doctor\Command;

use HexagonPractise\Application\Port\DoctorCommandPort;
use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Shared\PractitionerId;

final readonly class CreateDoctor
{
    public function __construct(private DoctorCommandPort $doctors)
    {
    }

    /** @return array{doctor_id: string, name: string} */
    public function execute(string $doctorId, string $name): array
    {
        $doctor = new Doctor(new PractitionerId($doctorId), $name);
        $this->doctors->save($doctor);

        return $doctor->toArray();
    }
}
