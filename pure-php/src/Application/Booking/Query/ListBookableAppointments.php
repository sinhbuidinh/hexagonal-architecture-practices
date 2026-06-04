<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Booking\Query;

use HexagonPractise\Application\Port\BookableSlotQueryPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Shared\PractitionerId;

/** Available time windows for one doctor — MedPro-style date + start/end list. */
final readonly class ListBookableAppointments
{
    public function __construct(
        private DoctorQueryPort $doctors,
        private BookableSlotQueryPort $bookableSlots,
    ) {
    }

    /**
     * @return list<array{slot_id: int, doctor_id: int, date: string, start_time: string, end_time: string}>
     */
    public function execute(int $doctorId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $practitionerId = new PractitionerId($doctorId);
        $doctor         = $this->doctors->find($practitionerId);
        if ($doctor === null) {
            throw new DoctorNotFoundException($practitionerId);
        }

        if (!$doctor->acceptingNewPatients) {
            return [];
        }

        $bookable = [];
        foreach ($this->bookableSlots->listAvailable($practitionerId, $dateFrom, $dateTo) as $slot) {
            $bookable[] = [
                'slot_id'    => $slot->id->value,
                'doctor_id'  => $doctorId,
                'date'       => $slot->date,
                'start_time' => $slot->startTime,
                'end_time'   => $slot->endTime,
            ];
        }

        return $bookable;
    }
}
