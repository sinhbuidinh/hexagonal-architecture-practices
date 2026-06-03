<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Event;

use HexagonPractise\Application\Event\ActionAudited;
use HexagonPractise\Application\Event\DomainExceptionOccurred;
use HexagonPractise\Application\Port\ActionAuditedListener;
use HexagonPractise\Application\Port\EventDispatcherPort;
use HexagonPractise\Application\Port\ExceptionResponseListener;

final class SyncEventDispatcher implements EventDispatcherPort
{
    /** @param list<ExceptionResponseListener> $exceptionListeners */
    /** @param list<ActionAuditedListener> $actionAuditedListeners */
    public function __construct(
        private readonly array $exceptionListeners,
        private readonly array $actionAuditedListeners,
    ) {
    }

    public function dispatch(object $event): void
    {
        if ($event instanceof DomainExceptionOccurred) {
            foreach ($this->exceptionListeners as $listener) {
                $listener->onDomainExceptionOccurred($event);
                if ($event->response !== null) {
                    break;
                }
            }

            return;
        }

        if ($event instanceof ActionAudited) {
            foreach ($this->actionAuditedListeners as $listener) {
                $listener->onActionAudited($event);
            }
        }
    }
}
