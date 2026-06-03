<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Patient\Patient;
use App\Domain\Shared\PatientId;

interface PatientQueryPort
{
    public function find(PatientId $id): ?Patient;
}
