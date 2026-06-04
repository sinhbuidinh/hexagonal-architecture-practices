<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Scheduling\BookableSlotUnavailableException;

final class BookableSlotUnavailableExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof BookableSlotUnavailableException) {
            return;
        }

        $e = $event->exception;
        $event->response = new HttpErrorResponse(409, [
            'error'   => $e->getMessage(),
            'slot_id' => $e->slotId->value,
            'status'  => $e->status,
        ]);
    }
}
