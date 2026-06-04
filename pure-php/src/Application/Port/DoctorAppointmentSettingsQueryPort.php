<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Doctor\DoctorAppointmentSettings;
use HexagonPractise\Domain\Shared\PractitionerId;

interface DoctorAppointmentSettingsQueryPort
{
    public function find(PractitionerId $practitionerId): ?DoctorAppointmentSettings;
}
