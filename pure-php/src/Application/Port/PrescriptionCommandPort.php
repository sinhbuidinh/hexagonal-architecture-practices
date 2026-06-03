<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Prescription\ConcurrentUpdateException;
use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Prescription\PrescriptionNotFoundException;

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
