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

    public function listRecent(int $limit = 100): array
    {
        if ($limit <= 0) {
            return [];
        }

        return array_slice($this->entries, -$limit);
    }
}
