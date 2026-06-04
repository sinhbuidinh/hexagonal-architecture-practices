<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Doctor\DoctorAppointmentSettings;
use App\Domain\Shared\PractitionerId;

interface DoctorAppointmentSettingsCommandPort
{
    public function save(DoctorAppointmentSettings $settings): void;

    public function ensureDefaults(PractitionerId $practitionerId): void;
}
