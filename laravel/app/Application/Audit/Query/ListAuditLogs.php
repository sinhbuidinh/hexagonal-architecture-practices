<?php

declare(strict_types=1);

namespace App\Application\Audit\Query;

use App\Application\Port\AuditLogPort;

final readonly class ListAuditLogs
{
    public function __construct(private AuditLogPort $auditLog)
    {
    }

    /** @return list<array<string, mixed>> */
    public function execute(int $limit = 100): array
    {
        return array_map(
            static fn ($entry) => $entry->toArray(),
            $this->auditLog->listRecent($limit),
        );
    }
}
