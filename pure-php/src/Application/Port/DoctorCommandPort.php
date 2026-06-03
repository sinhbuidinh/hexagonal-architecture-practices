<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Doctor\Doctor;

interface DoctorCommandPort
{
    public function save(Doctor $doctor): void;
}
