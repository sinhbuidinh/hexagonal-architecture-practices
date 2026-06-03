<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Prescription\Command;

use HexagonPractise\Application\Port\PrescriptionCommandPort;
use HexagonPractise\Application\Port\PrescriptionQueryPort;
use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Prescription\PrescriptionNotFoundException;
use HexagonPractise\Domain\Prescription\PrescriptionStatus;
use HexagonPractise\Domain\Prescription\UnauthorizedPrescriptionChangeException;
use HexagonPractise\Domain\Shared\ActorRole;
use HexagonPractise\Domain\Shared\PrescriptionId;

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
        $actor = ActorRole::fromString($actorRole);
        $current = $this->load($prescriptionId);

        $updated = new Prescription(
            $current->id,
            $current->patientId,
            $this->resolveField($actor, 'medication', $changes, $current->medication),
            $this->resolveField($actor, 'dosage', $changes, $current->dosage),
            $this->resolveField($actor, 'instructions', $changes, $current->instructions),
            isset($changes['status']) && $changes['status'] !== null
                ? PrescriptionStatus::fromString((string) $changes['status'])
                : $current->status,
            $this->resolveField($actor, 'pharmacy_notes', $changes, $current->pharmacyNotes),
            $current->version,
            $actor,
        );

        $this->assertRoleRules($actor, $changes);

        $saved = $this->prescriptions->updateIfVersionMatches($updated, $expectedVersion);

        return $saved->toArray();
    }

    private function load(string $prescriptionId): Prescription
    {
        $id = new PrescriptionId($prescriptionId);
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
            if ($actor === ActorRole::Doctor && $status === PrescriptionStatus::Dispensed) {
                throw new UnauthorizedPrescriptionChangeException($actor, 'status→dispensed');
            }
            if ($actor === ActorRole::Pharmacist && $status === PrescriptionStatus::Draft) {
                throw new UnauthorizedPrescriptionChangeException($actor, 'status→draft');
            }
        }
    }

    private function assertMayTouch(ActorRole $actor, string $field): void
    {
        $allowed = match ($actor) {
            ActorRole::Doctor     => ['medication', 'dosage', 'instructions', 'status'],
            ActorRole::Pharmacist => ['pharmacy_notes', 'status'],
        };

        if (!in_array($field, $allowed, true)) {
            throw new UnauthorizedPrescriptionChangeException($actor, $field);
        }
    }
}
