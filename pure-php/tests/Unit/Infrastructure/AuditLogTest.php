<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Infrastructure;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Application\Audit\AuditRecordBuilder;
use HexagonPractise\Application\Audit\AuditRequestContext;
use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Scheduling\Command\SetPractitionerAvailability;
use HexagonPractise\Domain\Audit\AuditOutcome;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Event\DomainExceptionHandler;
use HexagonPractise\Infrastructure\Event\Listener\DoctorNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\RecordAuditLogListener;
use HexagonPractise\Infrastructure\Event\SyncEventDispatcher;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryAuditLogAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use PHPUnit\Framework\TestCase;

final class AuditLogTest extends TestCase
{
    public function testMetadataFieldsArePersisted(): void
    {
        $auditLog   = new InMemoryAuditLogAdapter();
        $clock      = new FrozenClock(new \DateTimeImmutable('2026-06-03T09:48:42.123Z'));
        $dispatcher = new SyncEventDispatcher(
            [new DoctorNotFoundExceptionListener()],
            [new RecordAuditLogListener($auditLog)],
        );

        $request = new AuditRequestContext(
            actorId  : 'user_dr_smith_882',
            actorRole: 'Physician',
            patientId: 'pat_99513',
            ipAddress: '192.168.1.45',
            deviceId : 'iPad_Clinic_04',
        );

        $builder = new AuditRecordBuilder();
        $dispatcher->dispatch($builder->buildAuditedEvent(
            action     : AuditActions::APPOINTMENT_CANCEL,
            outcome    : AuditOutcome::SUCCESS,
            request    : $request,
            occurredAt : $clock->now(),
            beforeState: ['status' => 'scheduled'],
            afterState : ['status' => 'cancelled'],
        ));

        $entry = $auditLog->listRecent(1)[0];
        $row   = $entry->toArray();

        $this->assertSame('2026-06-03T09:48:42.123Z', $row['timestamp']);
        $this->assertSame('user_dr_smith_882', $row['actor_id']);
        $this->assertSame('Physician', $row['actor_role']);
        $this->assertSame('pat_99513', $row['patient_id']);
        $this->assertSame('APPOINTMENT_CANCEL', $row['action_type']);
        $this->assertSame('192.168.1.45', $row['ip_address']);
        $this->assertSame('iPad_Clinic_04', $row['device_id']);
        $this->assertStringContainsString('scheduled', (string) $row['state_diff']);
        $this->assertStringContainsString('cancelled', (string) $row['state_diff']);
    }

    public function testFailureAuditIncludesHttpStatus(): void
    {
        $auditLog   = new InMemoryAuditLogAdapter();
        $doctors    = new InMemoryDoctorAdapter();
        $scheduling = new InMemorySchedulingAdapter();
        $clock      = new FrozenClock(new \DateTimeImmutable('2026-06-03T12:00:00Z'));

        $dispatcher = new SyncEventDispatcher(
            [new DoctorNotFoundExceptionListener()],
            [new RecordAuditLogListener($auditLog)],
        );

        (new CreateDoctor($doctors))->execute('dr-1', 'Dr One');

        $handler = new DomainExceptionHandler($dispatcher, $clock);
        $handler->handle(
            new DoctorNotFoundException(new PractitionerId('missing')),
            AuditActions::AVAILABILITY_SET,
            new AuditRequestContext('reception_1', 'Receptionist', null, '10.0.0.8', 'desktop'),
        );

        $row = $auditLog->listRecent(1)[0]->toArray();
        $this->assertSame('failure', $row['outcome']);
        $this->assertSame(404, $row['http_status']);
        $this->assertSame('reception_1', $row['actor_id']);
    }
}
