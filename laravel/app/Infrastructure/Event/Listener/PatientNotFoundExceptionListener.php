<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Patient\PatientNotFoundException;

final class PatientNotFoundExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof PatientNotFoundException) {
            return;
        }

        $event->response = new HttpErrorResponse(404, ['error' => $event->exception->getMessage()]);
    }
}
