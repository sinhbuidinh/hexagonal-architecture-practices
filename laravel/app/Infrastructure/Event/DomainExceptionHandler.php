<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Application\Audit\AuditRecordBuilder;
use App\Application\Audit\AuditRequestContext;
use App\Application\Event\DomainExceptionOccurred;
use App\Application\Http\HttpErrorResponse;
use App\Application\Port\ClockPort;
use App\Application\Port\EventDispatcherPort;
use App\Domain\Audit\AuditOutcome;

final class DomainExceptionHandler
{
    public function __construct(
        private readonly EventDispatcherPort $dispatcher,
        private readonly ClockPort $clock,
        private readonly AuditRecordBuilder $auditRecordBuilder = new AuditRecordBuilder(),
    ) {
    }

    /**
     * @param array<string, mixed>|null $beforeState
     */
    public function handle(
        \Throwable $exception,
        ?string $auditAction = null,
        ?AuditRequestContext $auditRequest = null,
        ?array $beforeState = null,
    ): ?HttpErrorResponse {
        $event = new DomainExceptionOccurred($exception);

        $this->dispatcher->dispatch($event);

        $httpStatus = $event->response?->status;
        $action     = $auditAction ?? 'exception.' . $this->shortClass($exception);
        $request    = $auditRequest ?? AuditRequestContext::fromHttpHints();

        $this->dispatcher->dispatch($this->auditRecordBuilder->buildAuditedEvent(
            action          : $action,
            outcome         : AuditOutcome::FAILURE,
            request         : $request,
            occurredAt      : $this->clock->now(),
            beforeState     : $beforeState,
            afterState      : null,
            exceptionClass  : $exception::class,
            exceptionMessage: $exception->getMessage(),
            httpStatus      : $httpStatus,
        ));

        return $event->response;
    }

    private function shortClass(\Throwable $exception): string
    {
        $parts = explode('\\', $exception::class);

        return end($parts);
    }
}
