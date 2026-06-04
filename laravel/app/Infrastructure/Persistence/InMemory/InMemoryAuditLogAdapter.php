<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\AuditLogPort;
use App\Domain\Audit\AuditLogEntry;

/**
 * Append-only audit store (separate from scheduling/prescription persistence).
 * Production should use an immutable, access-restricted sink — not the primary app database.
 */
final class InMemoryAuditLogAdapter implements AuditLogPort
{
    /** @var list<AuditLogEntry> */
    private array $entries = [];

    private int $nextId = 1;

    public function append(AuditLogEntry $entry): void
    {
        $this->entries[] = new AuditLogEntry(
            id              : $entry->id !== '' ? $entry->id : (string) $this->nextId++,
            action          : $entry->action,
            outcome         : $entry->outcome,
            occurredAt      : $entry->occurredAt,
            actorId         : $entry->actorId,
            actorRole       : $entry->actorRole,
            patientId       : $entry->patientId,
            actionType      : $entry->actionType,
            ipAddress       : $entry->ipAddress,
            deviceId        : $entry->deviceId,
            beforeState     : $entry->beforeState,
            afterState      : $entry->afterState,
            stateDiff       : $entry->stateDiff,
            exceptionClass  : $entry->exceptionClass,
            exceptionMessage: $entry->exceptionMessage,
            httpStatus      : $entry->httpStatus,
        );
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
