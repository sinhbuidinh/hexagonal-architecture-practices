<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Scheduling\BookableSlotNotFoundException;

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
