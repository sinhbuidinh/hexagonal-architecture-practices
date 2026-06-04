<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\BookableSlotQueryPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Port\PatientQueryPort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Expiration\ExpiringItem;
use HexagonPractise\Domain\Patient\PatientNotFoundException;
use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Scheduling\BookableSlotNotFoundException;
use HexagonPractise\Domain\Scheduling\BookableSlotUnavailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

final readonly class HoldAppointment
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private BookableSlotCommandPort $bookableSlotCommands,
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
        string $appointmentId,
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
            id            : new AppointmentId($appointmentId),
            practitionerId: $practitioner,
            patientId     : $patient,
            slots         : new SlotCount(1),
            expiresAt     : $expiresAt,
            bookableSlotId: $slotId,
        ));

        $this->bookableSlotCommands->markHeld($slotId, $stored->id);

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
