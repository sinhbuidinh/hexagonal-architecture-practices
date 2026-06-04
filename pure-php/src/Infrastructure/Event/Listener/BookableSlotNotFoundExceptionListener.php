<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Event\Listener;

use HexagonPractise\Application\Event\DomainExceptionOccurred;
use HexagonPractise\Application\Http\HttpErrorResponse;
use HexagonPractise\Application\Port\ExceptionResponseListener;
use HexagonPractise\Domain\Scheduling\BookableSlotNotFoundException;

final class BookableSlotNotFoundExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof BookableSlotNotFoundException) {
            return;
        }

        $event->response = new HttpErrorResponse(404, [
            'error'   => $event->exception->getMessage(),
            'slot_id' => $event->exception->slotId->value,
        ]);
    }
}
