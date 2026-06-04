<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\Redis;

use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Domain\Expiration\ExpiringItem;
use Predis\Client;

final class RedisExpirationQueueAdapter implements ExpirationQueuePort
{
    private readonly string $pollScript;

    public function __construct(
        private readonly Client $redis,
        private readonly string $zsetKey,
        private readonly string $payloadKeyPrefix,
    ) {
        $this->pollScript = LuaScriptLoader::load('poll_expiration.lua');
    }

    public function schedule(ExpiringItem $item): void
    {
        $score   = (string) $item->expiresAt->getTimestamp();
        $payload = json_encode($item->payload, JSON_THROW_ON_ERROR);

        $this->redis->zadd($this->zsetKey, [$item->id => $score]);
        $this->redis->set($this->payloadKey($item->id), $payload);
    }

    public function cancel(string $itemId): void
    {
        $this->redis->zrem($this->zsetKey, $itemId);
        $this->redis->del([$this->payloadKey($itemId)]);
    }

    public function pollDue(\DateTimeImmutable $now, int $limit = 100): array
    {
        $raw = $this->redis->eval(
            $this->pollScript,
            2,
            $this->zsetKey,
            $this->payloadKeyPrefix,
            (string) $now->getTimestamp(),
            (string) $limit,
        );

        if (!is_array($raw) || $raw === []) {
            return [];
        }

        $items       = [];
        for ($i       = 0; $i < count($raw); $i += 2) {
            $id          = (string) $raw[$i];
            $payloadJson = (string) ($raw[$i + 1] ?? '{}');
            /** @var array<string, mixed> $payload */
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
            $score   = $this->inferExpiresAtFromPayload($payload, $now);
            $items[] = new ExpiringItem($id, $payload, $score);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function inferExpiresAtFromPayload(array $payload, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        if (isset($payload['expires_at']) && is_string($payload['expires_at'])) {
            return new \DateTimeImmutable($payload['expires_at']);
        }

        return $fallback;
    }

    private function payloadKey(string $itemId): string
    {
        return $this->payloadKeyPrefix . $itemId;
    }
}
