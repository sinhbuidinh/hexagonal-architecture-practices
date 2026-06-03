<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Domain\Expiration\ExpiringItem;

/**
 * Outbound port: time-ordered expiration queue (e.g. Redis ZSET).
 */
interface ExpirationQueuePort
{
    public function schedule(ExpiringItem $item): void;

    public function cancel(string $itemId): void;

    /**
     * Atomically fetch and remove up to $limit items whose expiration time <= $now.
     *
     * @return list<ExpiringItem>
     */
    public function pollDue(\DateTimeImmutable $now, int $limit = 100): array;
}
