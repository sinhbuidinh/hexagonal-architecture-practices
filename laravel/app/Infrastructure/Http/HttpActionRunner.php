<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditRecordBuilder;
use App\Application\Audit\AuditRequestContext;
use App\Application\Port\ClockPort;
use App\Application\Port\EventDispatcherPort;
use App\Domain\Audit\AuditOutcome;
use App\Infrastructure\Event\DomainExceptionHandler;

final class HttpActionRunner
{
    public function __construct(
        private readonly DomainExceptionHandler $exceptionHandler,
        private readonly EventDispatcherPort $dispatcher,
        private readonly ClockPort $clock,
        private readonly AuditRecordBuilder $auditRecordBuilder = new AuditRecordBuilder(),
    ) {
    }

    /**
     * @param  callable(): array<string, mixed>  $action
     * @param  array<string, mixed>|null  $beforeState  unsanitized domain snapshot (sanitized before persist)
     * @return array<string, mixed>
     */
    public function run(
        callable $action,
        string $auditAction,
        AuditRequestContext $auditRequest,
        ?array $beforeState = null,
        int $successStatus = 200,
    ): array {
        try {
            $payload    = $action();
            $afterState = $this->auditRecordBuilder->afterStateFromPayload($auditAction, $payload);

            $this->dispatcher->dispatch($this->auditRecordBuilder->buildAuditedEvent(
                action     : $auditAction,
                outcome    : AuditOutcome::SUCCESS,
                request    : $auditRequest,
                occurredAt : $this->clock->now(),
                beforeState: $beforeState,
                afterState : $afterState,
            ));

            return array_merge(['status' => $successStatus], $payload);
        } catch (\Throwable $e) {
            $response = $this->exceptionHandler->handle(
                exception   : $e,
                auditAction : $auditAction,
                auditRequest: $auditRequest,
                beforeState : $beforeState,
            );
            if ($response !== null) {
                return $response->toPayload();
            }

            throw $e;
        }
    }
}
