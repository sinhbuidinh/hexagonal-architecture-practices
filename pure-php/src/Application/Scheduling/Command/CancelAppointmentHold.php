<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Application\Port\SchedulingQueryPort;
use HexagonPractise\Domain\Shared\AppointmentId;

final readonly class CancelAppointmentHold
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

        $this->scheduling->cancelHold($id);
        $this->expirationQueue->cancel('appointment:' . $appointmentId);

        if ($hold?->bookableSlotId !== null) {
            $this->bookableSlots->release($hold->bookableSlotId);
        }
    }
}
