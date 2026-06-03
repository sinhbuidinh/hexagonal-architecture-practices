<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Audit\AuditLogEntry;

interface AuditLogPort
{
    public function append(AuditLogEntry $entry): void;

    /** @return list<AuditLogEntry> */
    public function listRecent(int $limit = 100): array;
}
