<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Doctor\Doctor;
use App\Domain\Shared\PractitionerId;

interface DoctorQueryPort
{
    public function find(PractitionerId $id): ?Doctor;

    /** @return list<Doctor> */
    public function listAll(): array;
}
