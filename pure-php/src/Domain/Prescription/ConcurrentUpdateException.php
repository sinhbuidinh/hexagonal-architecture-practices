<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Prescription;

use HexagonPractise\Domain\Shared\PrescriptionId;

/**
 * Raised when a doctor and pharmacist (or any two actors) race on the same prescription:
 * the second writer had a stale {@see expectedVersion}.
 */
final class ConcurrentUpdateException extends \DomainException
{
    public function __construct(
        public readonly PrescriptionId $prescriptionId,
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct(sprintf(
            'Concurrent update on prescription "%s": expected version %d but current is %d. Reload and retry.',
            $prescriptionId->value,
            $expectedVersion,
            $currentVersion,
        ));
    }
}
