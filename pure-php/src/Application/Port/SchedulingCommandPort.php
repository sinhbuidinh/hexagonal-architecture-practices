<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Scheduling\AppointmentNotFoundException;
use HexagonPractise\Domain\Scheduling\NoSlotsAvailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

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
