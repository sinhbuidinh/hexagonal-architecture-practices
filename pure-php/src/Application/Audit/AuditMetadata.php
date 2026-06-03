<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Audit;

/**
 * Required audit fields per compliance spec (actor, patient, action, network origin, state diff).
 */
final readonly class AuditMetadata
{
    /**
     * @param array<string, mixed>|null $beforeState sanitized snapshot
     * @param array<string, mixed>|null $afterState sanitized snapshot
     */
    public function __construct(
        public string $actorId,
        public string $actorRole,
        public ?string $patientId,
        public string $actionType,
        public string $ipAddress,
        public string $deviceId,
        public ?array $beforeState = null,
        public ?array $afterState = null,
        public ?string $stateDiff = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'actor_id'     => $this->actorId,
            'actor_role'   => $this->actorRole,
            'patient_id'   => $this->patientId,
            'action_type'  => $this->actionType,
            'ip_address'   => $this->ipAddress,
            'device_id'    => $this->deviceId,
            'before_state' => $this->beforeState,
            'after_state'  => $this->afterState,
            'state_diff'   => $this->stateDiff,
        ];
    }
}
