<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Shared\PractitionerId;

interface DoctorQueryPort
{
    public function find(PractitionerId $id): ?Doctor;

    /** @return list<Doctor> */
    public function listAll(): array;
}
