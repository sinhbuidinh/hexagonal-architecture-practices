<?php

declare(strict_types=1);

namespace App\Application\Doctor\Command;

use App\Application\Port\DoctorAppointmentSettingsCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Application\Scheduling\Command\MaterializeBookableSlots;
use App\Domain\Doctor\DoctorAppointmentSettings;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Shared\PractitionerId;

final readonly class UpdateDoctorAppointmentSettings
{
    public function __construct(
        private DoctorAppointmentSettingsCommandPort $settingsCommands,
        private DoctorQueryPort $doctors,
        private MaterializeBookableSlots $materializeBookableSlots,
    ) {
    }

    /**
     * @param array<string, mixed> $weeklySchedule
     *
     * @return array{
     *     settings: array{practitioner_id: int, slot_duration_minutes: int, weekly_schedule: array<string, array{start_time: string, end_time: string}|null>, timezone: string},
     *     materialized: array{practitioner_id: int, horizon_days: int, deleted: int, inserted: int, generated: int}
     * }
     */
    public function execute(
        int $practitionerId,
        int $slotDurationMinutes,
        array $weeklySchedule,
        ?string $timezone = null,
    ): array {
        $id = new PractitionerId($practitionerId);
        if ($this->doctors->find($id) === null) {
            throw new DoctorNotFoundException($id);
        }

        $settings = new DoctorAppointmentSettings(
            practitionerId     : $id,
            slotDurationMinutes: $slotDurationMinutes,
            weeklySchedule     : DoctorAppointmentSettings::weeklyScheduleFromArray($weeklySchedule),
            timezone           : $timezone ?? DoctorAppointmentSettings::DEFAULT_TIMEZONE,
        );

        $this->settingsCommands->save($settings);

        return [
            'settings'     => $settings->toArray(),
            'materialized' => $this->materializeBookableSlots->execute($practitionerId),
        ];
    }
}
