<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\PatientCommandPort;
use App\Application\Port\PatientQueryPort;
use App\Domain\Patient\Patient;
use App\Domain\Shared\PatientId;
use Illuminate\Support\Facades\DB;

final class MySqlPatientAdapter implements PatientCommandPort, PatientQueryPort
{
    public function create(
        string $name,
        ?int $userId = null,
        ?string $preferredLanguage = null,
        ?string $dateOfBirth = null,
        ?string $phone = null,
    ): Patient {
        $id = DB::table('patients')->insertGetId(values: [
            'user_id'            => $userId,
            'name'               => $name,
            'preferred_language' => $preferredLanguage,
            'date_of_birth'      => $dateOfBirth,
            'phone'              => $phone,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return new Patient(
            id               : new PatientId((string) $id),
            name             : $name,
            preferredLanguage: $preferredLanguage,
            dateOfBirth      : $dateOfBirth,
            phone            : $phone,
            userId           : $userId,
        );
    }

    public function save(Patient $patient): void
    {
        DB::table('patients')->updateOrInsert(
            attributes: ['id' => $patient->id->value],
            values    : [
                'user_id'            => $patient->userId,
                'name'               => $patient->name,
                'preferred_language' => $patient->preferredLanguage,
                'date_of_birth'      => $patient->dateOfBirth,
                'phone'              => $patient->phone,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        );
    }

    public function find(PatientId $id): ?Patient
    {
        $row = DB::table('patients')->where(column: 'id', operator: '=', value: $id->value)->first();
        if ($row === null) {
            return null;
        }

        return new Patient(
            id               : new PatientId((string) $row->id),
            name             : $row->name,
            preferredLanguage: $row->preferred_language,
            dateOfBirth      : $row->date_of_birth !== null ? (string) $row->date_of_birth : null,
            phone            : $row->phone,
            userId           : $row->user_id !== null ? (int) $row->user_id : null,
        );
    }
}
