<?php

declare(strict_types=1);

namespace App\Application\Audit;

use App\Application\Event\ActionAudited;
use App\Domain\Audit\AuditOutcome;
use App\Domain\Audit\AuditSensitiveDataSanitizer;
use App\Domain\Audit\AuditStateDiff;

final class AuditRecordBuilder
{
    public function __construct(
        private readonly AuditSensitiveDataSanitizer $sanitizer = new AuditSensitiveDataSanitizer(),
        private readonly AuditStateDiff $stateDiff = new AuditStateDiff(),
    ) {
    }

    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     */
    public function buildAuditedEvent(
        string $action,
        AuditOutcome $outcome,
        AuditRequestContext $request,
        \DateTimeImmutable $occurredAt,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $exceptionClass = null,
        ?string $exceptionMessage = null,
        ?int $httpStatus = null,
    ): ActionAudited {
        $safeBefore = $this->sanitizer->sanitize($beforeState);
        $safeAfter = $this->sanitizer->sanitize($afterState);

        return new ActionAudited(
            action: $action,
            outcome: $outcome,
            metadata: new AuditMetadata(
                actorId: $request->actorId,
                actorRole: $request->actorRole,
                patientId: $request->patientId,
                actionType: AuditActionType::fromAction($action),
                ipAddress: $request->ipAddress,
                deviceId: $request->deviceId,
                beforeState: $safeBefore,
                afterState: $safeAfter,
                stateDiff: $this->stateDiff->format($safeBefore, $safeAfter),
            ),
            occurredAt: $occurredAt,
            exceptionClass: $exceptionClass,
            exceptionMessage: $this->sanitizer->sanitizeMessage($exceptionMessage),
            httpStatus: $httpStatus,
        );
    }

    /**
     * Derive a safe after-state snapshot from a controller payload (ids, status, counts — not clinical narrative).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    public function afterStateFromPayload(string $action, array $payload): ?array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $this->snapshotFromData($payload['data']);
        }

        if (isset($payload['message'])) {
            return ['message' => $payload['message']];
        }

        if (isset($payload['processed'])) {
            return ['processed_count' => is_countable($payload['processed']) ? count($payload['processed']) : 0];
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function snapshotFromData(array $data): array
    {
        $allowed = [
            'doctor_id', 'patient_id', 'prescription_id', 'appointment_id',
            'practitioner_id', 'name', 'status', 'version', 'slots', 'available_slots',
            'expires_at', 'medication', 'dosage',
        ];

        $snapshot = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $snapshot[$key] = $data[$key];
            }
        }

        return $snapshot;
    }
}
