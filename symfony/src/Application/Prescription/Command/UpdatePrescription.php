<?php

declare(strict_types=1);

namespace App\Application\Prescription\Command;

use App\Application\Port\PrescriptionCommandPort;
use App\Application\Port\PrescriptionQueryPort;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use App\Domain\Prescription\PrescriptionStatus;
use App\Domain\Prescription\UnauthorizedPrescriptionChangeException;
use App\Domain\Shared\ActorRole;
use App\Domain\Shared\PrescriptionId;

/**
 * Doctor and pharmacist updates share one aggregate; {@see $expectedVersion} prevents lost updates.
 */
final readonly class UpdatePrescription
{
    public function __construct(
        private PrescriptionCommandPort $prescriptions,
        private PrescriptionQueryPort $prescriptionQueries,
    ) {
    }

    /**
     * @param array{
     *     medication?: string|null,
     *     dosage?: string|null,
     *     instructions?: string|null,
     *     status?: string|null,
     *     pharmacy_notes?: string|null,
     * } $changes
     *
     * @return array<string, mixed>
     */
    public function execute(
        string $prescriptionId,
        int $expectedVersion,
        string $actorRole,
        array $changes,
    ): array {
        $actor   = ActorRole::fromString($actorRole);
        $current = $this->load($prescriptionId);

        $updated = new Prescription(
            id           : $current->id,
            patientId    : $current->patientId,
            medication   : $this->resolveField($actor, 'medication', $changes, $current->medication),
            dosage       : $this->resolveField($actor, 'dosage', $changes, $current->dosage),
            instructions : $this->resolveField($actor, 'instructions', $changes, $current->instructions),
            status       : isset($changes['status']) && $changes['status'] !== null
                ? PrescriptionStatus::fromString((string) $changes['status'])
                : $current->status,
            pharmacyNotes: $this->resolveField($actor, 'pharmacy_notes', $changes, $current->pharmacyNotes),
            version      : $current->version,
            lastUpdatedBy: $actor,
        );

        $this->assertRoleRules($actor, $changes);

        $saved = $this->prescriptions->updateIfVersionMatches($updated, $expectedVersion);

        return $saved->toArray();
    }

    private function load(string $prescriptionId): Prescription
    {
        $id           = new PrescriptionId($prescriptionId);
        $prescription = $this->prescriptionQueries->find($id);
        if ($prescription === null) {
            throw new PrescriptionNotFoundException($id);
        }

        return $prescription;
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function resolveField(ActorRole $actor, string $field, array $changes, string $current): string
    {
        if (!array_key_exists($field, $changes) || $changes[$field] === null) {
            return $current;
        }

        $this->assertMayTouch($actor, $field);

        return (string) $changes[$field];
    }

    /** @param array<string, mixed> $changes */
    private function assertRoleRules(ActorRole $actor, array $changes): void
    {
        if (isset($changes['status']) && $changes['status'] !== null) {
            $status = PrescriptionStatus::fromString((string) $changes['status']);
            if ($actor === ActorRole::DOCTOR && $status === PrescriptionStatus::DISPENSED) {
                throw new UnauthorizedPrescriptionChangeException($actor, 'status→dispensed');
            }
            if ($actor === ActorRole::PHARMACIST && $status === PrescriptionStatus::DRAFT) {
                throw new UnauthorizedPrescriptionChangeException($actor, 'status→draft');
            }
        }
    }

    private function assertMayTouch(ActorRole $actor, string $field): void
    {
        $allowed = match ($actor) {
            ActorRole::DOCTOR     => ['medication', 'dosage', 'instructions', 'status'],
            ActorRole::PHARMACIST => ['pharmacy_notes', 'status'],
        };

        if (!in_array($field, $allowed, true)) {
            throw new UnauthorizedPrescriptionChangeException($actor, $field);
        }
    }
}
