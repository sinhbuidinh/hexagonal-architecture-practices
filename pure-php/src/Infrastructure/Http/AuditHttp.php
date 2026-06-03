<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditRequestContext;

/** Builds {@see AuditRequestContext} from JSON bodies and headers. */
final class AuditHttp
{
    /** @param array<string, mixed> $data decoded request body */
    public static function contextFrom(array $data = []): AuditRequestContext
    {
        return AuditRequestContext::fromHttpHints([
            'actor_id'   => $data['actor_id'] ?? $data['practitioner_id'] ?? $data['doctor_id'] ?? null,
            'actor'      => $data['actor'] ?? null,
            'actor_role' => $data['actor_role'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
        ]);
    }
}
