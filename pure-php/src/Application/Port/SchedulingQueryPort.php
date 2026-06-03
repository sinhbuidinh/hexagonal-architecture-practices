<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

/**
 * Outbound read port: slot counts and hold state without mutations.
 */
interface SchedulingQueryPort
{
    public function availableSlots(PractitionerId $practitionerId): SlotCount;

    public function findHold(AppointmentId $appointmentId): ?AppointmentHold;
}
