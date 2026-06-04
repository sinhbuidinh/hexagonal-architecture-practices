<?php

declare(strict_types=1);

namespace App\Application\Doctor\Query;

use App\Application\Port\DoctorAppointmentSettingsQueryPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\DoctorAppointmentSettingsNotFoundException;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Shared\PractitionerId;

final readonly class GetDoctorAppointmentSettings
{
    public function __construct(
        private DoctorAppointmentSettingsQueryPort $settings,
        private DoctorQueryPort $doctors,
    ) {
    }

    /**
     * @return array{practitioner_id: int, slot_duration_minutes: int, weekly_schedule: array<string, array{start_time: string, end_time: string}|null>, timezone: string}
     */
    public function execute(int $practitionerId): array
    {
        $id = new PractitionerId($practitionerId);
        if ($this->doctors->find($id) === null) {
            throw new DoctorNotFoundException($id);
        }

        $settings = $this->settings->find($id);
        if ($settings === null) {
            throw new DoctorAppointmentSettingsNotFoundException($id);
        }

        return $settings->toArray();
    }
}
