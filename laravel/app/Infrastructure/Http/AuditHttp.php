<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditRequestContext;
use Illuminate\Http\Request;

final class AuditHttp
{
    /** @param array<string, mixed> $extra */
    public static function merge(Request $request, array $extra = []): AuditRequestContext
    {
        $actorId = (string) (
            $extra['actor_id']
            ?? $extra['practitioner_id']
            ?? $extra['doctor_id']
            ?? $request->header('X-Actor-Id')
            ?? $request->header('X-User-Id')
            ?? $request->input('actor_id')
            ?? $request->input('actor')
            ?? 'system'
        );
        $actorRole = (string) (
            $extra['actor_role']
            ?? $request->header('X-Actor-Role')
            ?? $request->input('actor_role')
            ?? $request->input('actor')
            ?? 'System'
        );
        $patientId = isset($extra['patient_id']) && $extra['patient_id'] !== ''
            ? (string) $extra['patient_id']
            : self::nullableString($request->input('patient_id'));

        return new AuditRequestContext(
            actorId: $actorId,
            actorRole: $actorRole,
            patientId: $patientId,
            ipAddress: $request->ip() ?? 'unknown',
            deviceId: (string) ($request->header('X-Device-Id') ?? $request->userAgent() ?? 'unknown'),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
