<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class DatabaseConnectionFactory
{
    public static function fromDsn(string $dsn): Connection
    {
        return DriverManager::getConnection(['url' => $dsn]);
    }

    public static function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    /**
     * @param array<string, mixed> $identity
     * @param array<string, mixed> $values
     */
    public static function updateOrInsert(Connection $connection, string $table, array $identity, array $values): void
    {
        $criteria = $identity;
        $sql      = 'SELECT 1 FROM ' . $connection->quoteIdentifier($table)
            . ' WHERE ' . self::criteriaSql($connection, $criteria) . ' LIMIT 1';
        $existing = $connection->fetchOne(query: $sql, params: array_values($criteria));

        if ($existing !== false) {
            $connection->update($table, $values, $criteria);

            return;
        }

        $connection->insert($table, array_merge($identity, $values));
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private static function criteriaSql(Connection $connection, array $criteria): string
    {
        $parts = [];
        foreach (array_keys($criteria) as $column) {
            $parts[] = $connection->quoteIdentifier($column) . ' = ?';
        }

        return implode(' AND ', $parts);
    }
}
