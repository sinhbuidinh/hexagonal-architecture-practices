<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Domain\Shared\AppointmentId;

final readonly class CancelAppointmentHold
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private ExpirationQueuePort $expirationQueue,
    ) {
    }

    public function execute(string $appointmentId): void
    {
        $id = new AppointmentId($appointmentId);
        $this->scheduling->cancelHold($id);
        $this->expirationQueue->cancel('appointment:' . $appointmentId);
    }
}
