<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Patient\Patient;
use HexagonPractise\Domain\Shared\PatientId;

interface PatientQueryPort
{
    public function find(PatientId $id): ?Patient;
}
