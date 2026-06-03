<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Prescription\Prescription;
use App\Domain\Shared\PrescriptionId;

/**
 * Outbound read port: load prescriptions without side effects.
 */
interface PrescriptionQueryPort
{
    public function find(PrescriptionId $id): ?Prescription;
}
