<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ExceptionResponseListener;
use App\Domain\Audit\AuditLogAccessDeniedException;

final class AuditLogAccessDeniedExceptionListener implements ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void
    {
        if (!$event->exception instanceof AuditLogAccessDeniedException) {
            return;
        }

        $event->response = new HttpErrorResponse(403, ['error' => $event->exception->getMessage()]);
    }
}
