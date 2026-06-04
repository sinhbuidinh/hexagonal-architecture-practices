<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\BookableSlotQueryPort;
use App\Application\Port\DoctorQueryPort;
use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\PatientQueryPort;
use App\Application\Port\SchedulingCommandPort;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Expiration\ExpiringItem;
use App\Domain\Patient\PatientNotFoundException;
use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Scheduling\BookableSlotNotFoundException;
use App\Domain\Scheduling\BookableSlotUnavailableException;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\BookableSlotId;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

final readonly class HoldAppointment
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private BookableSlotQueryPort $bookableSlotQueries,
        private ExpirationQueuePort $expirationQueue,
        private DoctorQueryPort $doctors,
        private PatientQueryPort $patients,
    ) {
    }

    /**
     * @return array{appointment_id: string, practitioner_id: int, patient_id: string, bookable_slot_id: int, date: string, start_time: string, end_time: string, expires_at: string}
     */
    public function execute(
        int $practitionerId,
        string $patientId,
        int $bookableSlotId,
        \DateTimeImmutable $expiresAt,
    ): array {
        $practitioner = new PractitionerId($practitionerId);
        if ($this->doctors->find($practitioner) === null) {
            throw new DoctorNotFoundException($practitioner);
        }

        $patient = new PatientId($patientId);
        if ($this->patients->find($patient) === null) {
            throw new PatientNotFoundException($patient);
        }

        $slotId = new BookableSlotId($bookableSlotId);
        $slot   = $this->bookableSlotQueries->find($slotId);
        if ($slot === null) {
            throw new BookableSlotNotFoundException($slotId);
        }

        if ($slot->practitionerId->value !== $practitionerId) {
            throw new BookableSlotUnavailableException($slotId, $slot->status);
        }

        if (!$slot->isAvailable()) {
            throw new BookableSlotUnavailableException($slotId, $slot->status);
        }

        $stored = $this->scheduling->hold(new AppointmentHold(
            id            : new AppointmentId('pending'),
            practitionerId: $practitioner,
            patientId     : $patient,
            slots         : new SlotCount(1),
            expiresAt     : $expiresAt,
            bookableSlotId: $slotId,
        ));

        $appointmentId = $stored->id->value;

        $this->expirationQueue->schedule(new ExpiringItem(
            id       : 'appointment:' . $appointmentId,
            payload  : [
                'type'           => 'appointment_hold',
                'appointment_id' => $appointmentId,
                'expires_at'     => $expiresAt->format(\DateTimeInterface::ATOM),
            ],
            expiresAt: $expiresAt,
        ));

        return [
            'appointment_id'   => $appointmentId,
            'practitioner_id'  => $practitionerId,
            'patient_id'       => $patientId,
            'bookable_slot_id' => $bookableSlotId,
            'date'             => $slot->date,
            'start_time'       => $slot->startTime,
            'end_time'         => $slot->endTime,
            'expires_at'       => $expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
