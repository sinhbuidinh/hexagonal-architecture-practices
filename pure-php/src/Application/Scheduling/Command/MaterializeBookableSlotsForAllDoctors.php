<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\DoctorAppointmentSettingsQueryPort;
use HexagonPractise\Application\Port\DoctorQueryPort;

final readonly class MaterializeBookableSlotsForAllDoctors
{
    public function __construct(
        private DoctorQueryPort $doctors,
        private DoctorAppointmentSettingsQueryPort $appointmentSettings,
        private MaterializeBookableSlots $materializeBookableSlots,
    ) {
    }

    /**
     * @return list<array{practitioner_id: int, horizon_days: int, deleted: int, inserted: int, generated: int}>
     */
    public function execute(?int $horizonDays = null): array
    {
        $results = [];
        foreach ($this->doctors->listAll() as $doctor) {
            if ($this->appointmentSettings->find($doctor->id) === null) {
                continue;
            }

            $results[] = $this->materializeBookableSlots->execute($doctor->id->value, $horizonDays);
        }

        return $results;
    }
}
