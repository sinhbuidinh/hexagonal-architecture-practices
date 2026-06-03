<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Listener;

use App\Application\Event\ActionAudited;
use App\Application\Port\ActionAuditedListener;
use App\Application\Port\AuditLogPort;
use App\Domain\Audit\AuditLogEntry;

/**
 * Append-only audit sink (WORM semantics at port level — no update/delete on AuditLogPort).
 */
final class RecordAuditLogListener implements ActionAuditedListener
{
    public function __construct(private readonly AuditLogPort $auditLog)
    {
    }

    public function onActionAudited(ActionAudited $event): void
    {
        $meta = $event->metadata;

        $this->auditLog->append(new AuditLogEntry(
            id              : bin2hex(random_bytes(8)),
            action          : $event->action,
            outcome         : $event->outcome,
            occurredAt      : $event->occurredAt,
            actorId         : $meta->actorId,
            actorRole       : $meta->actorRole,
            patientId       : $meta->patientId,
            actionType      : $meta->actionType,
            ipAddress       : $meta->ipAddress,
            deviceId        : $meta->deviceId,
            beforeState     : $meta->beforeState,
            afterState      : $meta->afterState,
            stateDiff       : $meta->stateDiff,
            exceptionClass  : $event->exceptionClass,
            exceptionMessage: $event->exceptionMessage,
            httpStatus      : $event->httpStatus,
        ));
    }
}
