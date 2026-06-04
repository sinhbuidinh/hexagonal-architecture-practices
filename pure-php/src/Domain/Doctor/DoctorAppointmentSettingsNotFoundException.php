<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Doctor;

use HexagonPractise\Domain\Shared\PractitionerId;

final class DoctorAppointmentSettingsNotFoundException extends \DomainException
{
    public function __construct(PractitionerId $practitionerId)
    {
        parent::__construct(sprintf('Appointment settings not found for practitioner %d.', $practitionerId->value));
    }
}
