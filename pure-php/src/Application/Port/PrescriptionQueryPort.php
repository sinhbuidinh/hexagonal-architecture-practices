<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Shared\PrescriptionId;

/**
 * Outbound read port: load prescriptions without side effects.
 */
interface PrescriptionQueryPort
{
    public function find(PrescriptionId $id): ?Prescription;
}
