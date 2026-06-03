<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Prescription\ConcurrentUpdateException;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionNotFoundException;

/**
 * Outbound write port: persist and optimistic-lock updates.
 */
interface PrescriptionCommandPort
{
    public function save(Prescription $prescription): void;

    /**
     * @throws PrescriptionNotFoundException
     * @throws ConcurrentUpdateException
     */
    public function updateIfVersionMatches(Prescription $prescription, int $expectedVersion): Prescription;
}
