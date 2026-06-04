<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Infrastructure;

use HexagonPractise\Application\Audit\AuditRecordBuilder;
use HexagonPractise\Application\Audit\AuditRequestContext;
use HexagonPractise\Domain\Audit\AuditOutcome;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Prescription\ConcurrentUpdateException;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\PrescriptionId;
use HexagonPractise\Infrastructure\Clock\FrozenClock;
use HexagonPractise\Infrastructure\Event\DomainExceptionHandler;
use HexagonPractise\Infrastructure\Event\Listener\ConcurrentUpdateExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\DoctorNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\RecordAuditLogListener;
use HexagonPractise\Infrastructure\Event\SyncEventDispatcher;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryAuditLogAdapter;
use PHPUnit\Framework\TestCase;

final class DomainExceptionHandlerTest extends TestCase
{
    private DomainExceptionHandler $handler;
    private InMemoryAuditLogAdapter $auditLog;

    protected function setUp(): void
    {
        $this->auditLog = new InMemoryAuditLogAdapter();
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-03T12:00:00Z'));

        $dispatcher = new SyncEventDispatcher(
            [
                new DoctorNotFoundExceptionListener(),
                new ConcurrentUpdateExceptionListener(),
            ],
            [new RecordAuditLogListener($this->auditLog)],
        );

        $this->handler = new DomainExceptionHandler($dispatcher, $clock, new AuditRecordBuilder());
    }

    public function testDispatchesToMatchingListener(): void
    {
        $response = $this->handler->handle(
            new DoctorNotFoundException(new PractitionerId(99)),
            'availability.set',
            new AuditRequestContext('user_dr_smith_882', 'Physician', null, '192.168.1.45', 'iPad'),
            ['practitioner_id' => 99],
        );

        $this->assertNotNull($response);
        $this->assertSame(404, $response->status);

        $row = $this->auditLog->listRecent(1)[0]->toArray();
        $this->assertSame('failure', $row['outcome']);
        $this->assertSame('user_dr_smith_882', $row['actor_id']);
        $this->assertSame('AVAILABILITY_SET', $row['action_type']);
    }

    public function testReturnsNullWhenNoListenerMatches(): void
    {
        $this->assertNull($this->handler->handle(new \RuntimeException('unknown')));
        $this->assertSame('exception.RuntimeException', $this->auditLog->listRecent(1)[0]->action);
    }

    public function testConcurrentUpdateListenerAddsVersionFields(): void
    {
        $response = $this->handler->handle(new ConcurrentUpdateException(
            new PrescriptionId('rx-1'),
            1,
            2,
        ), 'prescription.update');

        $this->assertSame(409, $response?->status);
        $this->assertSame(1, $response?->body['expected_version']);
        $this->assertSame(2, $response?->body['current_version']);
    }
}
