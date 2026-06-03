<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Prescription\ConcurrentUpdateException;

final class ConcurrentUpdateExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof ConcurrentUpdateException) {
            return;
        }

        $e               = $event->exception;
        $event->response = new HttpErrorResponse(409, [
            'error'            => $e->getMessage(),
            'expected_version' => $e->expectedVersion,
            'current_version'  => $e->currentVersion,
            'hint'             => 'Reload the prescription and retry with the current version.',
        ]);
    }
}
