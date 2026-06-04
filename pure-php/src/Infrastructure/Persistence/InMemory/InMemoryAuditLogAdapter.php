<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\InMemory;

use HexagonPractise\Application\Port\AuditLogPort;
use HexagonPractise\Domain\Audit\AuditLogEntry;

/**
 * Append-only audit store (separate from scheduling/prescription persistence).
 * Production should use an immutable, access-restricted sink — not the primary app database.
 */
final class InMemoryAuditLogAdapter implements AuditLogPort
{
    /** @var list<AuditLogEntry> */
    private array $entries = [];

    public function append(AuditLogEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    public function listRecent(int $limit = 100, ?string $action = null, ?string $actorId = null): array
    {
        if ($limit <= 0) {
            return [];
        }

        $entries = $this->entries;
        if ($action !== null) {
            $entries = array_values(array_filter(
                $entries,
                static fn (AuditLogEntry $entry): bool => $entry->action === $action,
            ));
        }
        if ($actorId !== null) {
            $entries = array_values(array_filter(
                $entries,
                static fn (AuditLogEntry $entry): bool => $entry->actorId === $actorId,
            ));
        }

        return array_slice($entries, -$limit);
    }
}
