<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Application;

use HexagonPractise\Application\Prescription\Command\CreatePrescription;
use HexagonPractise\Application\Prescription\Command\UpdatePrescription;
use HexagonPractise\Application\Prescription\Query\GetPrescription;
use HexagonPractise\Domain\Prescription\ConcurrentUpdateException;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPrescriptionAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Doctor and pharmacist both read version 1; only the first writer succeeds.
 */
final class PrescriptionRaceTest extends TestCase
{
    private InMemoryPrescriptionAdapter $prescriptions;
    private UpdatePrescription $update;

    protected function setUp(): void
    {
        $this->prescriptions = new InMemoryPrescriptionAdapter();
        $this->update        = new UpdatePrescription($this->prescriptions, $this->prescriptions);

        (new CreatePrescription($this->prescriptions))->execute(
            'rx-1',
            'patient-42',
            'Amoxicillin',
            '500mg',
            'Twice daily',
        );
    }

    public function testDoctorWinsRacePharmacistMustRetry(): void
    {
        $doctor = $this->update->execute('rx-1', 1, 'doctor', [
            'dosage' => '500mg TID',
            'status' => 'active',
        ]);

        $this->assertSame(2, $doctor['version']);
        $this->assertSame('500mg TID', $doctor['dosage']);

        $this->expectException(ConcurrentUpdateException::class);
        $this->expectExceptionMessage('expected version 1 but current is 2');

        $this->update->execute('rx-1', 1, 'pharmacist', [
            'pharmacy_notes' => 'Ready for pickup',
            'status'         => 'dispensed',
        ]);
    }

    public function testPharmacistSucceedsAfterReload(): void
    {
        $this->update->execute('rx-1', 1, 'doctor', ['status' => 'active']);

        $current    = (new GetPrescription($this->prescriptions))->execute('rx-1');

        $pharmacist = $this->update->execute('rx-1', $current['version'], 'pharmacist', [
            'pharmacy_notes' => 'Dispensed at counter 2',
            'status'         => 'dispensed',
        ]);

        $this->assertSame(3, $pharmacist['version']);
        $this->assertSame('dispensed', $pharmacist['status']);
        $this->assertSame('Dispensed at counter 2', $pharmacist['pharmacy_notes']);
    }
}
