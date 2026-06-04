<?php

declare(strict_types=1);

namespace App\Application\Audit\Query;

use App\Application\Audit\AuditActions;
use App\Application\Audit\AuditLogListScope;
use App\Application\Port\AuditLogPort;

final readonly class ListAuditLogs
{
    public function __construct(private AuditLogPort $auditLog)
    {
    }

    /** @return list<array<string, mixed>> */
    public function execute(int $limit, string $action, AuditLogListScope $scope): array
    {
        if (! AuditActions::isKnown($action)) {
            throw new \InvalidArgumentException(sprintf('Unknown audit action: %s', $action));
        }

        return array_map(
            static fn ($entry) => $entry->toArray(),
            $this->auditLog->listRecent($limit, $action, $scope->actorId),
        );
    }
}
