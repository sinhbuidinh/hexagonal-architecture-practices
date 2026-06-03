<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\InMemory;

use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Domain\Expiration\ExpiringItem;

final class InMemoryExpirationQueueAdapter implements ExpirationQueuePort
{
    /** @var array<string, ExpiringItem> */
    private array $items = [];

    public function schedule(ExpiringItem $item): void
    {
        $this->items[$item->id] = $item;
    }

    public function cancel(string $itemId): void
    {
        unset($this->items[$itemId]);
    }

    public function pollDue(\DateTimeImmutable $now, int $limit = 100): array
    {
        $due = [];
        foreach ($this->items as $id => $item) {
            if ($item->isDue($now)) {
                $due[$id] = $item;
            }
        }

        uasort($due, static fn (ExpiringItem $a, ExpiringItem $b): int =>
            $a->expiresAt <=> $b->expiresAt);

        $polled = [];
        foreach (array_slice(array_values($due), 0, $limit) as $item) {
            unset($this->items[$item->id]);
            $polled[] = $item;
        }

        return $polled;
    }
}
