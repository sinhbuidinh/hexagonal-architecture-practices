<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

/** Temporary hold on practitioner slots until confirm or expiry. */
final readonly class AppointmentHold
{
    public function __construct(
        public AppointmentId $id,
        public PractitionerId $practitionerId,
        public PatientId $patientId,
        public SlotCount $slots,
        public \DateTimeImmutable $expiresAt,
        public ?BookableSlotId $bookableSlotId = null,
    ) {
        if ($slots->isZero()) {
            throw new \InvalidArgumentException('Appointment must reserve at least one slot.');
        }
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
