<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Port\PatientQueryPort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Expiration\ExpiringItem;
use HexagonPractise\Domain\Patient\PatientNotFoundException;
use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

final readonly class HoldAppointment
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private ExpirationQueuePort $expirationQueue,
        private DoctorQueryPort $doctors,
        private PatientQueryPort $patients,
    ) {
    }

    /**
     * @return array{appointment_id: string, practitioner_id: string, patient_id: string, slots: int, expires_at: string}
     */
    public function execute(
        string $appointmentId,
        string $practitionerId,
        string $patientId,
        int $slots,
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

        $hold = new AppointmentHold(
            id            : new AppointmentId($appointmentId),
            practitionerId: $practitioner,
            patientId     : $patient,
            slots         : new SlotCount($slots),
            expiresAt     : $expiresAt,
        );

        $this->scheduling->hold($hold);

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
            'appointment_id'  => $appointmentId,
            'practitioner_id' => $practitionerId,
            'patient_id'      => $patientId,
            'slots'           => $slots,
            'expires_at'      => $expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
