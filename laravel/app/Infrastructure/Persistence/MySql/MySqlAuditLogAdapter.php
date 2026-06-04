<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\AuditLogPort;
use App\Domain\Audit\AuditLogEntry;
use App\Domain\Audit\AuditOutcome;
use Illuminate\Support\Facades\DB;

final class MySqlAuditLogAdapter implements AuditLogPort
{
    public function append(AuditLogEntry $entry): void
    {
        DB::table('audit_logs')->insert(values: [
            'action'            => $entry->action,
            'outcome'           => $entry->outcome->value,
            'occurred_at'       => $entry->occurredAt->format('Y-m-d H:i:s.u'),
            'actor_id'          => $entry->actorId,
            'actor_role'        => $entry->actorRole,
            'patient_id'        => $entry->patientId,
            'action_type'       => $entry->actionType,
            'ip_address'        => $entry->ipAddress,
            'device_id'         => $entry->deviceId,
            'before_state'      => $entry->beforeState !== null ? json_encode($entry->beforeState) : null,
            'after_state'       => $entry->afterState !== null ? json_encode($entry->afterState) : null,
            'state_diff'        => $entry->stateDiff,
            'exception_class'   => $entry->exceptionClass,
            'exception_message' => $entry->exceptionMessage,
            'http_status'       => $entry->httpStatus,
        ]);
    }

    /** @return list<AuditLogEntry> */
    public function listRecent(int $limit = 100): array
    {
        if ($limit <= 0) {
            return [];
        }

        $rows = DB::table('audit_logs')
            ->orderByDesc(column: 'id')
            ->limit(value: $limit)
            ->get()
            ->reverse()
            ->values()
            ->all();

        return array_map(
            callback: fn (object $row): AuditLogEntry => $this->mapRow($row),
            array   : $rows,
        );
    }

    private function mapRow(object $row): AuditLogEntry
    {
        return new AuditLogEntry(
            id              : (string) $row->id,
            action          : $row->action,
            outcome         : AuditOutcome::from($row->outcome),
            occurredAt      : new \DateTimeImmutable($row->occurred_at),
            actorId         : $row->actor_id,
            actorRole       : $row->actor_role,
            patientId       : $row->patient_id,
            actionType      : $row->action_type,
            ipAddress       : $row->ip_address,
            deviceId        : $row->device_id,
            beforeState     : $row->before_state !== null ? json_decode($row->before_state, true) : null,
            afterState      : $row->after_state !== null ? json_decode($row->after_state, true) : null,
            stateDiff       : $row->state_diff,
            exceptionClass  : $row->exception_class,
            exceptionMessage: $row->exception_message,
            httpStatus      : $row->http_status !== null ? (int) $row->http_status : null,
        );
    }
}
