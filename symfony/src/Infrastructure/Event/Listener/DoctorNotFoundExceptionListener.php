<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Doctor\DoctorNotFoundException;

final class DoctorNotFoundExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof DoctorNotFoundException) {
            return;
        }

        $event->response = new HttpErrorResponse(404, ['error' => $event->exception->getMessage()]);
    }
}
