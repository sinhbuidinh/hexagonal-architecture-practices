<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Doctor;

use HexagonPractise\Domain\Shared\PractitionerId;

final class DoctorNotFoundException extends \RuntimeException
{
    public function __construct(PractitionerId $id)
    {
        parent::__construct(sprintf('Doctor "%s" not found.', $id->value));
    }
}
