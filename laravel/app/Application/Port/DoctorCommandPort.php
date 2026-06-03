<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Doctor\Doctor;

interface DoctorCommandPort
{
    public function save(Doctor $doctor): void;
}
