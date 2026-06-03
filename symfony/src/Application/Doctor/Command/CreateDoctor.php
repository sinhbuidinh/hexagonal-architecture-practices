<?php

declare(strict_types=1);

namespace App\Application\Doctor\Command;

use App\Application\Port\DoctorCommandPort;
use App\Domain\Doctor\Doctor;
use App\Domain\Shared\PractitionerId;

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
