<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Event\Listener;

use HexagonPractise\Application\Event\DomainExceptionOccurred;
use HexagonPractise\Application\Http\HttpErrorResponse;
use HexagonPractise\Application\Port\ExceptionResponseListener;
use HexagonPractise\Domain\Prescription\UnauthorizedPrescriptionChangeException;

final class UnauthorizedPrescriptionChangeExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof UnauthorizedPrescriptionChangeException) {
            return;
        }

        $event->response = new HttpErrorResponse(403, ['error' => $event->exception->getMessage()]);
    }
}
