<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditRequestContext;
use Symfony\Component\HttpFoundation\Request;

final class AuditHttp
{
    /** @param array<string, mixed> $extra */
    public static function merge(Request $request, array $extra = []): AuditRequestContext
    {
        $data      = $request->request->all();

        $actorId   = (string) (
            $extra['actor_id']
            ?? $extra['practitioner_id']
            ?? $extra['doctor_id']
            ?? $request->headers->get('X-Actor-Id')
            ?? $request->headers->get('X-User-Id')
            ?? ($data['actor_id'] ?? null)
            ?? ($data['actor'] ?? null)
            ?? 'system'
        );
        $actorRole = (string) (
            $extra['actor_role']
            ?? $request->headers->get('X-Actor-Role')
            ?? ($data['actor_role'] ?? null)
            ?? ($data['actor'] ?? null)
            ?? 'System'
        );
        $patientId = isset($extra['patient_id']) && $extra['patient_id'] !== ''
            ? (string) $extra['patient_id']
            : (isset($data['patient_id']) && $data['patient_id'] !== '' ? (string) $data['patient_id'] : null);

        return new AuditRequestContext(
            actorId  : $actorId,
            actorRole: $actorRole,
            patientId: $patientId,
            ipAddress: $request->getClientIp() ?? 'unknown',
            deviceId : (string) ($request->headers->get('X-Device-Id') ?? $request->headers->get('User-Agent') ?? 'unknown'),
        );
    }
}
