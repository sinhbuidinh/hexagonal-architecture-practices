<?php

declare(strict_types=1);

namespace App\Domain\Patient;

use App\Domain\Shared\PatientId;

final class PatientNotFoundException extends \RuntimeException
{
    public function __construct(PatientId $id)
    {
        parent::__construct(sprintf('Patient "%s" not found.', $id->value));
    }
}
