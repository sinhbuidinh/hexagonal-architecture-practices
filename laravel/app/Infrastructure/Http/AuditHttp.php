<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditRequestContext;
use App\Infrastructure\Auth\AuthenticatedUser;
use App\Infrastructure\Http\Middleware\AuthenticateJwt;
use Illuminate\Http\Request;

final class AuditHttp
{
    /** @param array<string, mixed> $extra */
    public static function merge(Request $request, array $extra = []): AuditRequestContext
    {
        $user = $request->attributes->get(AuthenticateJwt::REQUEST_ATTRIBUTE);
        $user = $user instanceof AuthenticatedUser ? $user : null;

        $actorId = (string) (
            $extra['actor_id']
            ?? $extra['practitioner_id']
            ?? $extra['doctor_id']
            ?? ($user !== null ? $user->id : null)
            ?? 'system'
        );
        $actorRole = (string) (
            $extra['actor_role']
            ?? ($user !== null ? $user->role->value : null)
            ?? 'System'
        );
        $patientId = isset($extra['patient_id']) && $extra['patient_id'] !== ''
            ? (string) $extra['patient_id']
            : self::nullableString($request->input('patient_id'));

        return new AuditRequestContext(
            actorId  : $actorId,
            actorRole: $actorRole,
            patientId: $patientId,
            ipAddress: $request->ip() ?? 'unknown',
            deviceId : (string) ($request->header('X-Device-Id') ?? $request->userAgent() ?? 'unknown'),
        );
    }

    public static function user(Request $request): ?AuthenticatedUser
    {
        $user = $request->attributes->get(AuthenticateJwt::REQUEST_ATTRIBUTE);

        return $user instanceof AuthenticatedUser ? $user : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
