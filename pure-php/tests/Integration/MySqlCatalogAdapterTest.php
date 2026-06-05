<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Integration;

use HexagonPractise\Domain\Doctor\Doctor;
use HexagonPractise\Domain\Patient\Patient;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Infrastructure\Persistence\MySql\DatabaseConnectionFactory;
use HexagonPractise\Infrastructure\Persistence\MySql\MySqlDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\MySql\MySqlPatientAdapter;
use PHPUnit\Framework\TestCase;

final class MySqlCatalogAdapterTest extends TestCase
{
    private ?MySqlDoctorAdapter $doctors   = null;
    private ?MySqlPatientAdapter $patients = null;

    protected function setUp(): void
    {
        $dsn = getenv('DATABASE_URL') ?: getenv('DATABASE_DSN') ?: 'mysql://root@127.0.0.1:3306/hexagon_practise';

        try {
            $connection = DatabaseConnectionFactory::fromDsn($dsn);
            $connection->executeQuery('SELECT 1');
            $this->doctors = new MySqlDoctorAdapter($connection);
            $this->patients = new MySqlPatientAdapter($connection);
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL is not available: ' . $e->getMessage());
        }
    }

    public function testDoctorRoundTrip(): void
    {
        $id = 9_001_001 + random_int(0, 999);

        $doctor = new Doctor(
            id                  : new PractitionerId($id),
            name                : 'Dr Integration',
            specialties         : ['Cardiology'],
            languages           : ['English'],
            licenseNumber       : 'LIC-INT',
            acceptingNewPatients: true,
        );

        $this->doctors->save($doctor);

        $found = $this->doctors->find(new PractitionerId($id));
        self::assertNotNull($found);
        self::assertSame('Dr Integration', $found->name);
        self::assertSame(['Cardiology'], $found->specialties);

        $connection = DatabaseConnectionFactory::fromDsn(
            getenv('DATABASE_URL') ?: getenv('DATABASE_DSN') ?: 'mysql://root@127.0.0.1:3306/hexagon_practise',
        );
        $connection->delete('doctors', ['id' => $id]);
    }

    public function testPatientRoundTrip(): void
    {
        $id = (string) (9_002_001 + random_int(0, 999));

        $patient = new Patient(
            id               : new PatientId($id),
            name             : 'Jane Integration',
            preferredLanguage: 'en',
        );

        $this->patients->save($patient);

        $found = $this->patients->find(new PatientId($id));
        self::assertNotNull($found);
        self::assertSame('Jane Integration', $found->name);

        $connection = DatabaseConnectionFactory::fromDsn(
            getenv('DATABASE_URL') ?: getenv('DATABASE_DSN') ?: 'mysql://root@127.0.0.1:3306/hexagon_practise',
        );
        $connection->delete('patients', ['id' => (int) $id]);
    }
}
