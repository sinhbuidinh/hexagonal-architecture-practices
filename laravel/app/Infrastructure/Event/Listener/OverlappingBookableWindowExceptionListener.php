<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Scheduling\OverlappingBookableWindowException;

final class OverlappingBookableWindowExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof OverlappingBookableWindowException) {
            return;
        }

        $e = $event->exception;
        $event->response = new HttpErrorResponse(409, [
            'error'          => $e->getMessage(),
            'incoming'       => $e->incoming,
            'conflicts_with' => $e->conflictsWith,
        ]);
    }
}
