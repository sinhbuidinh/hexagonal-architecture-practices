<?php

declare(strict_types=1);

namespace HexagonPractise\Bootstrap;

use HexagonPractise\Application\Audit\Query\ListAuditLogs;
use HexagonPractise\Application\Booking\Query\ListBookableAppointments;
use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Expiration\ProcessExpiredItems;
use HexagonPractise\Application\Patient\Command\CreatePatient;
use HexagonPractise\Application\Port\AuditLogPort;
use HexagonPractise\Application\Port\ClockPort;
use HexagonPractise\Application\Port\DoctorCommandPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Application\Port\ExpirationQueuePort;
use HexagonPractise\Application\Port\PatientCommandPort;
use HexagonPractise\Application\Port\PatientQueryPort;
use HexagonPractise\Application\Port\PrescriptionCommandPort;
use HexagonPractise\Application\Port\PrescriptionQueryPort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Application\Port\SchedulingQueryPort;
use HexagonPractise\Application\Prescription\Command\CreatePrescription;
use HexagonPractise\Application\Prescription\Command\UpdatePrescription;
use HexagonPractise\Application\Prescription\Query\GetPrescription;
use HexagonPractise\Application\Scheduling\Command\CancelAppointmentHold;
use HexagonPractise\Application\Scheduling\Command\ConfirmAppointment;
use HexagonPractise\Application\Scheduling\Command\HoldAppointment;
use HexagonPractise\Application\Scheduling\Command\SetPractitionerAvailability;
use HexagonPractise\Infrastructure\Clock\SystemClock;
use HexagonPractise\Infrastructure\Event\DomainExceptionHandler;
use HexagonPractise\Infrastructure\Event\Listener\AppointmentNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\ConcurrentUpdateExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\DoctorNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\NoSlotsAvailableExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\PatientNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\PrescriptionNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\RecordAuditLogListener;
use HexagonPractise\Infrastructure\Event\Listener\UnauthorizedPrescriptionChangeExceptionListener;
use HexagonPractise\Infrastructure\Event\SyncEventDispatcher;
use HexagonPractise\Infrastructure\Http\HttpActionRunner;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryAuditLogAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPatientAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPrescriptionAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisClientFactory;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisPrescriptionAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisSchedulingAdapter;

final class Container
{
    private readonly SchedulingCommandPort $schedulingCommands;
    private readonly SchedulingQueryPort $schedulingQueries;
    private readonly ExpirationQueuePort $expirationQueue;
    private readonly PrescriptionCommandPort $prescriptionCommands;
    private readonly PrescriptionQueryPort $prescriptionQueries;
    private readonly DoctorCommandPort $doctorCommands;
    private readonly DoctorQueryPort $doctorQueries;
    private readonly PatientCommandPort $patientCommands;
    private readonly PatientQueryPort $patientQueries;
    private readonly ClockPort $clock;

    public readonly SetPractitionerAvailability $setPractitionerAvailability;
    public readonly HoldAppointment $holdAppointment;
    public readonly CancelAppointmentHold $cancelAppointmentHold;
    public readonly ConfirmAppointment $confirmAppointment;
    public readonly ProcessExpiredItems $processExpiredItems;
    public readonly CreatePrescription $createPrescription;
    public readonly GetPrescription $getPrescription;
    public readonly UpdatePrescription $updatePrescription;
    public readonly CreateDoctor $createDoctor;
    public readonly CreatePatient $createPatient;
    public readonly ListBookableAppointments $listBookableAppointments;
    public readonly ListAuditLogs $listAuditLogs;
    public readonly HttpActionRunner $httpActionRunner;
    private readonly AuditLogPort $auditLog;

    public static function fromConfig(array $config, bool $useInMemory = false): self
    {
        $doctors = new InMemoryDoctorAdapter();
        $patients = new InMemoryPatientAdapter();
        $auditLog = new InMemoryAuditLogAdapter();

        if ($useInMemory) {
            $scheduling = new InMemorySchedulingAdapter();
            $prescriptions = new InMemoryPrescriptionAdapter();

            return new self(
                $scheduling,
                $scheduling,
                new InMemoryExpirationQueueAdapter(),
                $prescriptions,
                $prescriptions,
                $doctors,
                $doctors,
                $patients,
                $patients,
                $auditLog,
                new SystemClock(),
            );
        }

        $redis = RedisClientFactory::fromDsn($config['redis_dsn']);
        $scheduling = new RedisSchedulingAdapter(
            $redis,
            $config['slots_key_prefix'],
            $config['appointment_key_prefix'],
        );
        $prescriptions = new RedisPrescriptionAdapter(
            $redis,
            $config['prescription_key_prefix'],
        );

        return new self(
            $scheduling,
            $scheduling,
            new RedisExpirationQueueAdapter(
                $redis,
                $config['expiration_zset_key'],
                $config['expiration_payload_prefix'],
            ),
            $prescriptions,
            $prescriptions,
            $doctors,
            $doctors,
            $patients,
            $patients,
            $auditLog,
            new SystemClock(),
        );
    }

    private function __construct(
        SchedulingCommandPort $schedulingCommands,
        SchedulingQueryPort $schedulingQueries,
        ExpirationQueuePort $expirationQueue,
        PrescriptionCommandPort $prescriptionCommands,
        PrescriptionQueryPort $prescriptionQueries,
        DoctorCommandPort $doctorCommands,
        DoctorQueryPort $doctorQueries,
        PatientCommandPort $patientCommands,
        PatientQueryPort $patientQueries,
        AuditLogPort $auditLog,
        ClockPort $clock,
    ) {
        $this->schedulingCommands = $schedulingCommands;
        $this->schedulingQueries = $schedulingQueries;
        $this->expirationQueue = $expirationQueue;
        $this->prescriptionCommands = $prescriptionCommands;
        $this->prescriptionQueries = $prescriptionQueries;
        $this->doctorCommands = $doctorCommands;
        $this->doctorQueries = $doctorQueries;
        $this->patientCommands = $patientCommands;
        $this->patientQueries = $patientQueries;
        $this->auditLog = $auditLog;
        $this->clock = $clock;

        $this->setPractitionerAvailability = new SetPractitionerAvailability($schedulingCommands, $doctorQueries);
        $this->cancelAppointmentHold = new CancelAppointmentHold($schedulingCommands, $expirationQueue);
        $this->holdAppointment = new HoldAppointment(
            $schedulingCommands,
            $expirationQueue,
            $doctorQueries,
            $patientQueries,
        );
        $this->confirmAppointment = new ConfirmAppointment($schedulingCommands, $expirationQueue);
        $this->processExpiredItems = new ProcessExpiredItems(
            $expirationQueue,
            $this->cancelAppointmentHold,
            $clock,
        );

        $this->createPrescription = new CreatePrescription($prescriptionCommands);
        $this->getPrescription = new GetPrescription($prescriptionQueries);
        $this->updatePrescription = new UpdatePrescription($prescriptionCommands, $prescriptionQueries);

        $this->createDoctor = new CreateDoctor($doctorCommands);
        $this->createPatient = new CreatePatient($patientCommands);
        $this->listBookableAppointments = new ListBookableAppointments($doctorQueries, $schedulingQueries);
        $this->listAuditLogs = new ListAuditLogs($auditLog);

        $auditListener = new RecordAuditLogListener($auditLog);
        $exceptionDispatcher = new SyncEventDispatcher(
            [
                new DoctorNotFoundExceptionListener(),
                new PatientNotFoundExceptionListener(),
                new PrescriptionNotFoundExceptionListener(),
                new AppointmentNotFoundExceptionListener(),
                new NoSlotsAvailableExceptionListener(),
                new ConcurrentUpdateExceptionListener(),
                new UnauthorizedPrescriptionChangeExceptionListener(),
            ],
            [$auditListener],
        );
        $exceptionHandler = new DomainExceptionHandler($exceptionDispatcher, $clock);
        $this->httpActionRunner = new HttpActionRunner($exceptionHandler, $exceptionDispatcher, $clock);
    }

    public function auditLog(): AuditLogPort
    {
        return $this->auditLog;
    }

    public function schedulingCommands(): SchedulingCommandPort
    {
        return $this->schedulingCommands;
    }

    public function schedulingQueries(): SchedulingQueryPort
    {
        return $this->schedulingQueries;
    }

    public function expirationQueue(): ExpirationQueuePort
    {
        return $this->expirationQueue;
    }

    public function prescriptionCommands(): PrescriptionCommandPort
    {
        return $this->prescriptionCommands;
    }

    public function prescriptionQueries(): PrescriptionQueryPort
    {
        return $this->prescriptionQueries;
    }

    public function clock(): ClockPort
    {
        return $this->clock;
    }
}
