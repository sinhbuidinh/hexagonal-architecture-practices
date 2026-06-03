<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\DoctorQueryPort;
use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\PatientQueryPort;
use App\Application\Port\SchedulingCommandPort;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Expiration\ExpiringItem;
use App\Domain\Patient\PatientNotFoundException;
use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

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
            new AppointmentId($appointmentId),
            $practitioner,
            $patient,
            new SlotCount($slots),
            $expiresAt,
        );

        $this->scheduling->hold($hold);

        $this->expirationQueue->schedule(new ExpiringItem(
            id: 'appointment:' . $appointmentId,
            payload: [
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
