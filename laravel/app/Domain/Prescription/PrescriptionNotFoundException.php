<?php

declare(strict_types=1);

namespace App\Domain\Prescription;

use App\Domain\Shared\PrescriptionId;

final class PrescriptionNotFoundException extends \DomainException
{
    public function __construct(public readonly PrescriptionId $prescriptionId)
    {
        parent::__construct(sprintf('Prescription "%s" not found.', $prescriptionId->value));
    }
}
