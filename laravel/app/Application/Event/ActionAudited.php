<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Application\Audit\AuditMetadata;
use App\Domain\Audit\AuditOutcome;

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
