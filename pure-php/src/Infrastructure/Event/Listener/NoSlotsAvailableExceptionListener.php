<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Event\Listener;

use HexagonPractise\Application\Event\DomainExceptionOccurred;
use HexagonPractise\Application\Http\HttpErrorResponse;
use HexagonPractise\Application\Port\ExceptionResponseListener;
use HexagonPractise\Domain\Scheduling\NoSlotsAvailableException;

final class NoSlotsAvailableExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof NoSlotsAvailableException) {
            return;
        }

        $e = $event->exception;
        $event->response = new HttpErrorResponse(409, [
            'error'           => $e->getMessage(),
            'available_slots' => $e->available->value,
        ]);
    }
}
