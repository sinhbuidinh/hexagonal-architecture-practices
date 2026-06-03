<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Audit;

final readonly class AuditLogEntry
{
    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     */
    public function __construct(
        public string $id,
        public string $action,
        public AuditOutcome $outcome,
        public \DateTimeImmutable $occurredAt,
        public string $actorId,
        public string $actorRole,
        public ?string $patientId,
        public string $actionType,
        public string $ipAddress,
        public string $deviceId,
        public ?array $beforeState = null,
        public ?array $afterState = null,
        public ?string $stateDiff = null,
        public ?string $exceptionClass = null,
        public ?string $exceptionMessage = null,
        public ?int $httpStatus = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'timestamp'         => $this->formatTimestamp($this->occurredAt),
            'actor_id'          => $this->actorId,
            'actor_role'        => $this->actorRole,
            'patient_id'        => $this->patientId,
            'action_type'       => $this->actionType,
            'action'            => $this->action,
            'outcome'           => $this->outcome->value,
            'ip_address'        => $this->ipAddress,
            'device_id'         => $this->deviceId,
            'before_state'      => $this->beforeState,
            'after_state'       => $this->afterState,
            'state_diff'        => $this->stateDiff,
            'exception_class'   => $this->exceptionClass,
            'exception_message' => $this->exceptionMessage,
            'http_status'       => $this->httpStatus,
        ];
    }

    private function formatTimestamp(\DateTimeImmutable $occurredAt): string
    {
        return $occurredAt
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');
    }
}
