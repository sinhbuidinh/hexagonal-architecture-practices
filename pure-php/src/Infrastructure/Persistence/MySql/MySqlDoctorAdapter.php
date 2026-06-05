<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use HexagonPractise\Application\Port\DoctorCommandPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Shared\PractitionerId;

final class MySqlDoctorAdapter implements DoctorCommandPort, DoctorQueryPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(Doctor $doctor): void
    {
        $now = DatabaseConnectionFactory::now();

        DatabaseConnectionFactory::updateOrInsert(
            connection: $this->connection,
            table     : 'doctors',
            identity  : ['id' => $doctor->id->value],
            values    : [
                'user_id'                => $doctor->userId,
                'name'                   => $doctor->name,
                'specialties'            => json_encode($doctor->specialties, JSON_THROW_ON_ERROR),
                'languages'              => json_encode($doctor->languages, JSON_THROW_ON_ERROR),
                'license_number'         => $doctor->licenseNumber,
                'accepting_new_patients' => $doctor->acceptingNewPatients,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        );
    }

    public function find(PractitionerId $id): ?Doctor
    {
        $row = $this->connection->fetchAssociative(
            query : 'SELECT * FROM doctors WHERE id = ?',
            params: [$id->value],
        );

        if ($row === false) {
            return null;
        }

        return $this->rowToDoctor($row);
    }

    /** @return list<Doctor> */
    public function listAll(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM doctors ORDER BY id');

        return array_map(
            callback: fn (array $row): Doctor => $this->rowToDoctor($row),
            array   : $rows,
        );
    }

    /** @param array<string, mixed> $row */
    private function rowToDoctor(array $row): Doctor
    {
        return new Doctor(
            id                  : new PractitionerId((int) $row['id']),
            name                : (string) $row['name'],
            specialties         : $this->decodeStringList($row['specialties'] ?? null),
            languages           : $this->decodeStringList($row['languages'] ?? null),
            licenseNumber       : $row['license_number'] !== null ? (string) $row['license_number'] : null,
            acceptingNewPatients: (bool) $row['accepting_new_patients'],
            userId              : $row['user_id'] !== null ? (int) $row['user_id'] : null,
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
