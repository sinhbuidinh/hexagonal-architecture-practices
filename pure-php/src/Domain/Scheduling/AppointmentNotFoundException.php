<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

use HexagonPractise\Domain\Shared\AppointmentId;

final class AppointmentNotFoundException extends \DomainException
{
    public function __construct(public readonly AppointmentId $appointmentId)
    {
        parent::__construct(sprintf('Appointment "%s" not found.', $appointmentId->value));
    }
}
