<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Doctor\DoctorAppointmentSettings;
use HexagonPractise\Domain\Shared\PractitionerId;

interface DoctorAppointmentSettingsCommandPort
{
    public function save(DoctorAppointmentSettings $settings): void;

    public function ensureDefaults(PractitionerId $practitionerId): void;
}
