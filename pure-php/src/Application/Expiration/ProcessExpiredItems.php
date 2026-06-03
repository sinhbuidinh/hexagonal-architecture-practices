<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Expiration;

use HexagonPractise\Application\Port\ClockPort;
use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Scheduling\Command\CancelAppointmentHold;

final readonly class ProcessExpiredItems
{
    public function __construct(
        private ExpirationQueuePort $expirationQueue,
        private CancelAppointmentHold $cancelAppointmentHold,
        private ClockPort $clock,
    ) {
    }

    /**
     * @return list<array{id: string, action: string}>
     */
    public function execute(int $limit = 100): array
    {
        $processed = [];
        $items     = $this->expirationQueue->pollDue($this->clock->now(), $limit);

        foreach ($items as $item) {
            if (($item->payload['type'] ?? '') === 'appointment_hold') {
                $appointmentId = (string) ($item->payload['appointment_id'] ?? '');
                if ($appointmentId !== '') {
                    try {
                        $this->cancelAppointmentHold->execute($appointmentId);
                        $processed[] = ['id' => $item->id, 'action' => 'cancelled_appointment_hold'];
                    } catch (\DomainException) {
                        $processed[] = ['id' => $item->id, 'action' => 'skipped_not_found'];
                    }
                }
            } else {
                $processed[] = ['id' => $item->id, 'action' => 'unknown_type'];
            }
        }

        return $processed;
    }
}
