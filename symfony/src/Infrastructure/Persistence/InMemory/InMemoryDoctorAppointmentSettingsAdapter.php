<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\DoctorAppointmentSettingsCommandPort;
use App\Application\Port\DoctorAppointmentSettingsQueryPort;
use App\Domain\Doctor\DoctorAppointmentSettings;
use App\Domain\Shared\PractitionerId;

final class InMemoryDoctorAppointmentSettingsAdapter implements DoctorAppointmentSettingsCommandPort, DoctorAppointmentSettingsQueryPort
{
    /** @var array<int, DoctorAppointmentSettings> */
    private array $store = [];

    public function save(DoctorAppointmentSettings $settings): void
    {
        $this->store[$settings->practitionerId->value] = $settings;
    }

    public function ensureDefaults(PractitionerId $practitionerId): void
    {
        if (!isset($this->store[$practitionerId->value])) {
            $this->store[$practitionerId->value] = DoctorAppointmentSettings::defaultFor($practitionerId);
        }
    }

    public function find(PractitionerId $practitionerId): ?DoctorAppointmentSettings
    {
        return $this->store[$practitionerId->value] ?? null;
    }
}
