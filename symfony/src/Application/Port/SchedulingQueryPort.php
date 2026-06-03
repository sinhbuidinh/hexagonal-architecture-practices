<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

/**
 * Outbound read port: slot counts and hold state without mutations.
 */
interface SchedulingQueryPort
{
    public function availableSlots(PractitionerId $practitionerId): SlotCount;

    public function findHold(AppointmentId $appointmentId): ?AppointmentHold;
}
