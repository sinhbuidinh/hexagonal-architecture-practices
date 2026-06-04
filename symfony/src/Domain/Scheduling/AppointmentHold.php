<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\BookableSlotId;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

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
