<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Scheduling\AppointmentNotFoundException;
use App\Domain\Scheduling\NoSlotsAvailableException;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

/**
 * Outbound write port: availability and appointment hold lifecycle mutations.
 */
interface SchedulingCommandPort
{
    public function setAvailability(PractitionerId $practitionerId, SlotCount $slots): void;

    /**
     * @throws NoSlotsAvailableException
     */
    public function hold(AppointmentHold $hold): void;

    /**
     * @throws AppointmentNotFoundException
     */
    public function cancelHold(AppointmentId $appointmentId): void;

    /**
     * @throws AppointmentNotFoundException
     */
    public function confirm(AppointmentId $appointmentId): void;
}
