<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use HexagonPractise\Application\Port\AuditLogPort;
use HexagonPractise\Domain\Audit\AuditLogEntry;
use HexagonPractise\Domain\Audit\AuditOutcome;

final class MySqlAuditLogAdapter implements AuditLogPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function append(AuditLogEntry $entry): void
    {
        $this->connection->insert(table: 'audit_logs', data : [
            'action'            => $entry->action,
            'outcome'           => $entry->outcome->value,
            'occurred_at'       => $entry->occurredAt->format('Y-m-d H:i:s.u'),
            'actor_id'          => $entry->actorId,
            'actor_role'        => $entry->actorRole,
            'patient_id'        => $entry->patientId,
            'action_type'       => $entry->actionType,
            'ip_address'        => $entry->ipAddress,
            'device_id'         => $entry->deviceId,
            'before_state'      => $entry->beforeState !== null ? json_encode($entry->beforeState, JSON_THROW_ON_ERROR) : null,
            'after_state'       => $entry->afterState !== null ? json_encode($entry->afterState, JSON_THROW_ON_ERROR) : null,
            'state_diff'        => $entry->stateDiff,
            'exception_class'   => $entry->exceptionClass,
            'exception_message' => $entry->exceptionMessage,
            'http_status'       => $entry->httpStatus,
        ]);
    }

    /** @return list<AuditLogEntry> */
    public function listRecent(int $limit = 100, ?string $action = null, ?string $actorId = null): array
    {
        if ($limit <= 0) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(table: 'audit_logs')
            ->orderBy(sort: 'id', order: 'DESC')
            ->setMaxResults(maxResults: $limit);

        if ($action !== null) {
            $qb->andWhere('action = :action')->setParameter('action', $action);
        }

        if ($actorId !== null) {
            $qb->andWhere('actor_id = :actor_id')->setParameter('actor_id', $actorId);
        }

        $rows = array_reverse($qb->fetchAllAssociative());

        return array_map(
            callback: fn (array $row): AuditLogEntry => $this->mapRow($row),
            array   : $rows,
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): AuditLogEntry
    {
        return new AuditLogEntry(
            id              : (string) $row['id'],
            action          : (string) $row['action'],
            outcome         : AuditOutcome::from((string) $row['outcome']),
            occurredAt      : new \DateTimeImmutable((string) $row['occurred_at']),
            actorId         : (string) $row['actor_id'],
            actorRole       : (string) $row['actor_role'],
            patientId       : $row['patient_id'] !== null ? (string) $row['patient_id'] : null,
            actionType      : (string) $row['action_type'],
            ipAddress       : (string) $row['ip_address'],
            deviceId        : (string) $row['device_id'],
            beforeState     : $row['before_state'] !== null ? json_decode((string) $row['before_state'], true) : null,
            afterState      : $row['after_state'] !== null ? json_decode((string) $row['after_state'], true) : null,
            stateDiff       : $row['state_diff'] !== null ? (string) $row['state_diff'] : null,
            exceptionClass  : $row['exception_class'] !== null ? (string) $row['exception_class'] : null,
            exceptionMessage: $row['exception_message'] !== null ? (string) $row['exception_message'] : null,
            httpStatus      : $row['http_status'] !== null ? (int) $row['http_status'] : null,
        );
    }
}
