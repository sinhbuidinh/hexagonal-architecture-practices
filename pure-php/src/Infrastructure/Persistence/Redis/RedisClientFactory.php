<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\Redis;

use Predis\Client;

final class RedisClientFactory
{
    public static function fromDsn(string $dsn): Client
    {
        return new Client($dsn);
    }
}
