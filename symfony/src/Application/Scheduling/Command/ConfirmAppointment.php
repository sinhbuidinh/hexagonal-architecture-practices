<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\BookableSlotCommandPort;
use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\SchedulingCommandPort;
use App\Application\Port\SchedulingQueryPort;
use App\Domain\Shared\AppointmentId;

final readonly class ConfirmAppointment
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private SchedulingQueryPort $schedulingQueries,
        private BookableSlotCommandPort $bookableSlots,
        private ExpirationQueuePort $expirationQueue,
    ) {
    }

    public function execute(string $appointmentId): void
    {
        $id   = new AppointmentId($appointmentId);
        $hold = $this->schedulingQueries->findHold($id);

        $this->scheduling->confirm($id);
        $this->expirationQueue->cancel('appointment:' . $appointmentId);

        if ($hold?->bookableSlotId !== null) {
            $this->bookableSlots->markConfirmed($hold->bookableSlotId);
        }
    }
}
