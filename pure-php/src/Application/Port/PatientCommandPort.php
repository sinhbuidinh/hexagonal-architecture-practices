<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Patient\Patient;

interface PatientCommandPort
{
    public function save(Patient $patient): void;
}
