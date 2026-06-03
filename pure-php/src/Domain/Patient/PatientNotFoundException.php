<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Patient;

use HexagonPractise\Domain\Shared\PatientId;

final class PatientNotFoundException extends \RuntimeException
{
    public function __construct(PatientId $id)
    {
        parent::__construct(sprintf('Patient "%s" not found.', $id->value));
    }
}
