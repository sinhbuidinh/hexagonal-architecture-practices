<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Event;

use HexagonPractise\Application\Audit\AuditMetadata;
use HexagonPractise\Domain\Audit\AuditOutcome;

/** Dispatched after a tracked HTTP action succeeds or fails. */
final readonly class ActionAudited
{
    public function __construct(
        public string $action,
        public AuditOutcome $outcome,
        public AuditMetadata $metadata,
        public \DateTimeImmutable $occurredAt,
        public ?string $exceptionClass = null,
        public ?string $exceptionMessage = null,
        public ?int $httpStatus = null,
    ) {
    }
}
