<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\DoctorCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\Doctor;
use App\Domain\Shared\PractitionerId;
use Illuminate\Support\Facades\DB;

final class MySqlDoctorAdapter implements DoctorCommandPort, DoctorQueryPort
{
    public function create(
        string $name,
        ?int $userId = null,
        array $specialties = [],
        array $languages = [],
        ?string $licenseNumber = null,
        bool $acceptingNewPatients = true,
    ): Doctor {
        $id = DB::table('doctors')->insertGetId(values: [
            'user_id'                => $userId,
            'name'                   => $name,
            'specialties'            => json_encode(Doctor::normalizeStringList($specialties), JSON_THROW_ON_ERROR),
            'languages'              => json_encode(Doctor::normalizeStringList($languages), JSON_THROW_ON_ERROR),
            'license_number'         => $licenseNumber,
            'accepting_new_patients' => $acceptingNewPatients,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return new Doctor(
            id                  : new PractitionerId($id),
            name                : $name,
            specialties         : Doctor::normalizeStringList($specialties),
            languages           : Doctor::normalizeStringList($languages),
            licenseNumber       : $licenseNumber,
            acceptingNewPatients: $acceptingNewPatients,
            userId              : $userId,
        );
    }

    public function save(Doctor $doctor): void
    {
        DB::table('doctors')->updateOrInsert(
            attributes: ['id' => $doctor->id->value],
            values    : [
                'user_id'                => $doctor->userId,
                'name'                   => $doctor->name,
                'specialties'            => json_encode($doctor->specialties, JSON_THROW_ON_ERROR),
                'languages'              => json_encode($doctor->languages, JSON_THROW_ON_ERROR),
                'license_number'         => $doctor->licenseNumber,
                'accepting_new_patients' => $doctor->acceptingNewPatients,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
        );
    }

    public function find(PractitionerId $id): ?Doctor
    {
        $row = DB::table('doctors')->where(column: 'id', operator: '=', value: $id->value)->first();
        if ($row === null) {
            return null;
        }

        return $this->rowToDoctor($row);
    }

    /** @return list<Doctor> */
    public function listAll(): array
    {
        return array_map(
            callback: fn (object $row): Doctor => $this->rowToDoctor($row),
            array   : DB::table('doctors')->orderBy(column: 'id')->get()->all(),
        );
    }

    private function rowToDoctor(object $row): Doctor
    {
        return new Doctor(
            id                  : new PractitionerId((int) $row->id),
            name                : $row->name,
            specialties         : $this->decodeStringList($row->specialties ?? null),
            languages           : $this->decodeStringList($row->languages ?? null),
            licenseNumber       : $row->license_number,
            acceptingNewPatients: (bool) $row->accepting_new_patients,
            userId              : $row->user_id !== null ? (int) $row->user_id : null,
        );
    }

    /** @return list<string> */
    private function decodeStringList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $decoded = $value;
        }

        return is_array($decoded) ? Doctor::normalizeStringList($decoded) : [];
    }
}
