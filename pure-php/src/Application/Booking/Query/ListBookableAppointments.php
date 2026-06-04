<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Booking\Query;

use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Application\Port\SchedulingQueryPort;

/** Doctors with at least one open slot — what a patient can book. */
final readonly class ListBookableAppointments
{
    public function __construct(
        private DoctorQueryPort $doctors,
        private SchedulingQueryPort $scheduling,
    ) {
    }

    /**
     * @return list<array{doctor_id: string, name: string, available_slots: int}>
     */
    public function execute(): array
    {
        $bookable = [];

        foreach ($this->doctors->listAll() as $doctor) {
            $slots = $this->scheduling->availableSlots($doctor->id);
            if ($slots->value <= 0) {
                continue;
            }

            $bookable[] = [
                'doctor_id'       => $doctor->id->value,
                'name'            => $doctor->name,
                'available_slots' => $slots->value,
            ];
        }

        return $bookable;
    }
}
