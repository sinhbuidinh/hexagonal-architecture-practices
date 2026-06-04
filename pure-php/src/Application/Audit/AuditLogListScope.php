<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Audit;

/** Row filter for audit log queries (enforced in persistence, not from client input). */
final readonly class AuditLogListScope
{
    public function __construct(public ?string $actorId = null)
    {
    }

    public static function unrestricted(): self
    {
        return new self();
    }

    public static function forActor(string $actorId): self
    {
        return new self(actorId: $actorId);
    }
}
