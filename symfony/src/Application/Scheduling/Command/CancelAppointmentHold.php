<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\SchedulingCommandPort;
use App\Domain\Shared\AppointmentId;

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
