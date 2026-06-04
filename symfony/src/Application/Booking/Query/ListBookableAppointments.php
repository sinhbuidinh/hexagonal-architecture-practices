<?php

declare(strict_types=1);

namespace App\Application\Booking\Query;

use App\Application\Port\BookableSlotQueryPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Shared\PractitionerId;

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
