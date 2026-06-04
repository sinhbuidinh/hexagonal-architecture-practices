<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\PrescriptionCommandPort;
use App\Application\Port\PrescriptionQueryPort;
use App\Domain\Prescription\ConcurrentUpdateException;
use App\Domain\Prescription\Prescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use App\Domain\Prescription\PrescriptionStatus;
use App\Domain\Shared\ActorRole;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PrescriptionId;
use Illuminate\Support\Facades\DB;

final class MySqlPrescriptionAdapter implements PrescriptionCommandPort, PrescriptionQueryPort
{
    public function create(
        PatientId $patientId,
        string $medication,
        string $dosage,
        string $instructions,
    ): Prescription {
        $id = DB::table('prescriptions')->insertGetId(values: [
            'patient_id'      => $patientId->value,
            'medication'      => $medication,
            'dosage'          => $dosage,
            'instructions'    => $instructions,
            'status'          => PrescriptionStatus::DRAFT->value,
            'pharmacy_notes'  => '',
            'version'         => 1,
            'last_updated_by' => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return new Prescription(
            id           : new PrescriptionId((string) $id),
            patientId    : $patientId,
            medication   : $medication,
            dosage       : $dosage,
            instructions : $instructions,
            status       : PrescriptionStatus::DRAFT,
            pharmacyNotes: '',
            version      : 1,
            lastUpdatedBy: null,
        );
    }

    public function save(Prescription $prescription): void
    {
        DB::table('prescriptions')->updateOrInsert(
            attributes: ['id' => $prescription->id->value],
            values    : [
                'patient_id'      => $prescription->patientId->value,
                'medication'      => $prescription->medication,
                'dosage'          => $prescription->dosage,
                'instructions'    => $prescription->instructions,
                'status'          => $prescription->status->value,
                'pharmacy_notes'  => $prescription->pharmacyNotes,
                'version'         => $prescription->version,
                'last_updated_by' => $prescription->lastUpdatedBy?->value,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        );
    }

    public function find(PrescriptionId $id): ?Prescription
    {
        $row = DB::table('prescriptions')->where(column: 'id', operator: '=', value: $id->value)->first();
        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function updateIfVersionMatches(Prescription $prescription, int $expectedVersion): Prescription
    {
        return DB::transaction(callback: function () use ($prescription, $expectedVersion): Prescription {
            $row = DB::table('prescriptions')
                ->where(column: 'id', operator: '=', value: $prescription->id->value)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new PrescriptionNotFoundException($prescription->id);
            }

            if ((int) $row->version !== $expectedVersion) {
                throw new ConcurrentUpdateException(
                    prescriptionId : $prescription->id,
                    expectedVersion: $expectedVersion,
                    currentVersion : (int) $row->version,
                );
            }

            $newVersion = $expectedVersion + 1;

            DB::table('prescriptions')
                ->where(column: 'id', operator: '=', value: $prescription->id->value)
                ->update(values: [
                    'medication'      => $prescription->medication,
                    'dosage'          => $prescription->dosage,
                    'instructions'    => $prescription->instructions,
                    'status'          => $prescription->status->value,
                    'pharmacy_notes'  => $prescription->pharmacyNotes,
                    'version'         => $newVersion,
                    'last_updated_by' => $prescription->lastUpdatedBy?->value,
                    'updated_at'      => now(),
                ]);

            return new Prescription(
                id           : $prescription->id,
                patientId    : $prescription->patientId,
                medication   : $prescription->medication,
                dosage       : $prescription->dosage,
                instructions : $prescription->instructions,
                status       : $prescription->status,
                pharmacyNotes: $prescription->pharmacyNotes,
                version      : $newVersion,
                lastUpdatedBy: $prescription->lastUpdatedBy,
            );
        });
    }

    private function mapRow(object $row): Prescription
    {
        return new Prescription(
            id           : new PrescriptionId((string) $row->id),
            patientId    : new PatientId((string) $row->patient_id),
            medication   : $row->medication,
            dosage       : $row->dosage,
            instructions : $row->instructions,
            status       : PrescriptionStatus::fromString($row->status),
            pharmacyNotes: $row->pharmacy_notes,
            version      : (int) $row->version,
            lastUpdatedBy: $row->last_updated_by !== null
                ? ActorRole::fromString($row->last_updated_by)
                : null,
        );
    }
}
