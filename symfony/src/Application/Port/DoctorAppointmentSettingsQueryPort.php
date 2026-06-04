<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Doctor\DoctorAppointmentSettings;
use App\Domain\Shared\PractitionerId;

interface DoctorAppointmentSettingsQueryPort
{
    public function find(PractitionerId $practitionerId): ?DoctorAppointmentSettings;
}
