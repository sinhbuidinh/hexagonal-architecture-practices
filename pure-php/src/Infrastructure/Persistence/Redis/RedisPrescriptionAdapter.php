<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\Redis;

use HexagonPractise\Application\Port\PrescriptionCommandPort;
use HexagonPractise\Application\Port\PrescriptionQueryPort;
use HexagonPractise\Domain\Prescription\ConcurrentUpdateException;
use HexagonPractise\Domain\Prescription\Prescription;
use HexagonPractise\Domain\Prescription\PrescriptionNotFoundException;
use HexagonPractise\Domain\Prescription\PrescriptionStatus;
use HexagonPractise\Domain\Shared\ActorRole;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PrescriptionId;
use Predis\Client;

final class RedisPrescriptionAdapter implements PrescriptionCommandPort, PrescriptionQueryPort
{
    private readonly string $updateScript;

    public function __construct(
        private readonly Client $redis,
        private readonly string $keyPrefix,
    ) {
        $this->updateScript = LuaScriptLoader::load('update_prescription.lua');
    }

    public function save(Prescription $prescription): void
    {
        $this->redis->hset($this->key($prescription->id), [
            'patient_id'      => $prescription->patientId->value,
            'medication'      => $prescription->medication,
            'dosage'          => $prescription->dosage,
            'instructions'    => $prescription->instructions,
            'status'          => $prescription->status->value,
            'pharmacy_notes'  => $prescription->pharmacyNotes,
            'version'         => (string) $prescription->version,
            'last_updated_by' => $prescription->lastUpdatedBy?->value ?? '',
        ]);
    }

    public function find(PrescriptionId $id): ?Prescription
    {
        $data = $this->redis->hgetall($this->key($id));
        if ($data === [] || !isset($data['patient_id'], $data['version'])) {
            return null;
        }

        return $this->hydrate($id, $data);
    }

    public function updateIfVersionMatches(Prescription $prescription, int $expectedVersion): Prescription
    {
        if ($this->find($prescription->id) === null) {
            throw new PrescriptionNotFoundException($prescription->id);
        }

        $result = $this->redis->eval(
            $this->updateScript,
            1,
            $this->key($prescription->id),
            (string) $expectedVersion,
            $prescription->medication,
            $prescription->dosage,
            $prescription->instructions,
            $prescription->status->value,
            $prescription->pharmacyNotes,
            $prescription->lastUpdatedBy?->value ?? '',
        );

        if (!is_array($result) || (int) ($result[0] ?? 0) !== 1) {
            $currentVersion = (int) ($result[1] ?? -1);
            if ($currentVersion === -1) {
                throw new PrescriptionNotFoundException($prescription->id);
            }

            throw new ConcurrentUpdateException(
                prescriptionId : $prescription->id,
                expectedVersion: $expectedVersion,
                currentVersion : $currentVersion,
            );
        }

        return new Prescription(
            id           : $prescription->id,
            patientId    : $prescription->patientId,
            medication   : $prescription->medication,
            dosage       : $prescription->dosage,
            instructions : $prescription->instructions,
            status       : $prescription->status,
            pharmacyNotes: $prescription->pharmacyNotes,
            version      : (int) $result[1],
            lastUpdatedBy: $prescription->lastUpdatedBy,
        );
    }

    /** @param array<string, string> $data */
    private function hydrate(PrescriptionId $id, array $data): Prescription
    {
        $lastUpdatedBy = ($data['last_updated_by'] ?? '') !== ''
            ? ActorRole::fromString($data['last_updated_by'])
            : null;

        return new Prescription(
            id           : $id,
            patientId    : new PatientId($data['patient_id']),
            medication   : $data['medication'] ?? '',
            dosage       : $data['dosage'] ?? '',
            instructions : $data['instructions'] ?? '',
            status       : PrescriptionStatus::fromString($data['status'] ?? PrescriptionStatus::DRAFT->value),
            pharmacyNotes: $data['pharmacy_notes'] ?? '',
            version      : (int) ($data['version'] ?? 1),
            lastUpdatedBy: $lastUpdatedBy,
        );
    }

    private function key(PrescriptionId $id): string
    {
        return $this->keyPrefix . $id->value;
    }
}
