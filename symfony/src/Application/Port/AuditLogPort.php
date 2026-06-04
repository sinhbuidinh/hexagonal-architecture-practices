<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Audit\AuditLogEntry;

interface AuditLogPort
{
    public function append(AuditLogEntry $entry): void;

    /** @return list<AuditLogEntry> */
    public function listRecent(int $limit = 100, ?string $action = null, ?string $actorId = null): array;
}
