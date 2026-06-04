<?php

declare(strict_types=1);

namespace App\Application\Audit;

/**
 * Caller identity and network origin extracted from the inbound HTTP request.
 */
final readonly class AuditRequestContext
{
    public function __construct(
        public string $actorId,
        public string $actorRole,
        public ?string $patientId,
        public string $ipAddress,
        public string $deviceId,
    ) {
    }

    /** @param array<string, mixed> $hints keys: actor_id, actor, actor_role, patient_id */
    public static function fromHttpHints(array $hints = []): self
    {
        $actorId = (string) ($hints['actor_id']
            ?? $_SERVER['HTTP_X_ACTOR_ID']
            ?? $_SERVER['HTTP_X_USER_ID']
            ?? 'system');
        $actorRole = (string) ($hints['actor_role']
            ?? $hints['actor']
            ?? $_SERVER['HTTP_X_ACTOR_ROLE']
            ?? 'System');
        $patientId = isset($hints['patient_id']) && $hints['patient_id'] !== ''
            ? (string) $hints['patient_id']
            : null;

        return new self(
            actorId  : $actorId,
            actorRole: $actorRole,
            patientId: $patientId,
            ipAddress: self::resolveClientIp(),
            deviceId : (string) ($_SERVER['HTTP_X_DEVICE_ID'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
        );
    }

    public function withPatientId(?string $patientId): self
    {
        return new self(
            actorId  : $this->actorId,
            actorRole: $this->actorRole,
            patientId: $patientId !== '' ? $patientId : null,
            ipAddress: $this->ipAddress,
            deviceId : $this->deviceId,
        );
    }

    public function withActor(string $actorId, string $actorRole): self
    {
        return new self($actorId, $actorRole, $this->patientId, $this->ipAddress, $this->deviceId);
    }

    private static function resolveClientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $ip = trim(explode(',', $value)[0]);

            return $ip !== '' ? $ip : 'unknown';
        }

        return 'unknown';
    }
}
