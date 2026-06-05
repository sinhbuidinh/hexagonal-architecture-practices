<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use HexagonPractise\Application\Port\PatientCommandPort;
use HexagonPractise\Application\Port\PatientQueryPort;
use HexagonPractise\Domain\Patient\Patient;
use HexagonPractise\Domain\Shared\PatientId;

final class MySqlPatientAdapter implements PatientCommandPort, PatientQueryPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(Patient $patient): void
    {
        if (!ctype_digit($patient->id->value)) {
            throw new \InvalidArgumentException(
                'MySQL patient id must be numeric (e.g. "42"). Got: ' . $patient->id->value,
            );
        }

        $now = DatabaseConnectionFactory::now();

        DatabaseConnectionFactory::updateOrInsert(
            connection: $this->connection,
            table     : 'patients',
            identity  : ['id' => (int) $patient->id->value],
            values    : [
                'user_id'            => $patient->userId,
                'name'               => $patient->name,
                'preferred_language' => $patient->preferredLanguage,
                'date_of_birth'      => $patient->dateOfBirth,
                'phone'              => $patient->phone,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        );
    }

    public function find(PatientId $id): ?Patient
    {
        if (!ctype_digit($id->value)) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            query : 'SELECT * FROM patients WHERE id = ?',
            params: [(int) $id->value],
        );

        if ($row === false) {
            return null;
        }

        return new Patient(
            id               : new PatientId((string) $row['id']),
            name             : (string) $row['name'],
            preferredLanguage: $row['preferred_language'] !== null ? (string) $row['preferred_language'] : null,
            dateOfBirth      : $row['date_of_birth'] !== null ? (string) $row['date_of_birth'] : null,
            phone            : $row['phone'] !== null ? (string) $row['phone'] : null,
            userId           : $row['user_id'] !== null ? (int) $row['user_id'] : null,
        );
    }
}
