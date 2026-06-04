<?php

declare(strict_types=1);

namespace HexagonPractise\Bootstrap;

use HexagonPractise\Application\Audit\Query\ListAuditLogs;
use HexagonPractise\Application\Booking\Query\ListBookableAppointments;
use HexagonPractise\Application\Doctor\Command\CreateDoctor;
use HexagonPractise\Application\Doctor\Command\UpdateDoctorAppointmentSettings;
use HexagonPractise\Application\Doctor\Query\GetDoctorAppointmentSettings;
use HexagonPractise\Application\Expiration\ProcessExpiredItems;
use HexagonPractise\Application\Patient\Command\CreatePatient;
use HexagonPractise\Application\Port\AuditLogPort;
use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\BookableSlotHorizonPort;
use HexagonPractise\Application\Port\BookableSlotQueryPort;
use HexagonPractise\Application\Port\ClockPort;
use HexagonPractise\Application\Port\DoctorAppointmentSettingsCommandPort;
use HexagonPractise\Application\Port\DoctorAppointmentSettingsQueryPort;
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
use HexagonPractise\Application\Scheduling\Command\MaterializeBookableSlots;
use HexagonPractise\Application\Scheduling\Command\MaterializeBookableSlotsForAllDoctors;
use HexagonPractise\Application\Scheduling\Command\PublishBookableSlots;
use HexagonPractise\Application\Scheduling\Command\SetPractitionerAvailability;
use HexagonPractise\Domain\Scheduling\BookableSlotGenerator;
use HexagonPractise\Infrastructure\Clock\SystemClock;
use HexagonPractise\Infrastructure\Event\DomainExceptionHandler;
use HexagonPractise\Infrastructure\Event\Listener\AppointmentNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\BookableSlotNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\BookableSlotUnavailableExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\ConcurrentUpdateExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\DoctorAppointmentSettingsNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\DoctorNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\NoSlotsAvailableExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\OverlappingBookableWindowExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\PatientNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\PrescriptionNotFoundExceptionListener;
use HexagonPractise\Infrastructure\Event\Listener\RecordAuditLogListener;
use HexagonPractise\Infrastructure\Event\Listener\UnauthorizedPrescriptionChangeExceptionListener;
use HexagonPractise\Infrastructure\Event\SyncEventDispatcher;
use HexagonPractise\Infrastructure\Http\HttpActionRunner;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryAuditLogAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryBookableSlotAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryDoctorAppointmentSettingsAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPatientAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemoryPrescriptionAdapter;
use HexagonPractise\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisClientFactory;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisExpirationQueueAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisPrescriptionAdapter;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisSchedulingAdapter;
use HexagonPractise\Infrastructure\Scheduling\ClinicLunchBreakFromConfig;
use HexagonPractise\Infrastructure\Scheduling\FixedBookableSlotHorizon;

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
    public readonly PublishBookableSlots $publishBookableSlots;
    public readonly HoldAppointment $holdAppointment;
    public readonly CancelAppointmentHold $cancelAppointmentHold;
    public readonly ConfirmAppointment $confirmAppointment;
    public readonly ProcessExpiredItems $processExpiredItems;
    public readonly CreatePrescription $createPrescription;
    public readonly GetPrescription $getPrescription;
    public readonly UpdatePrescription $updatePrescription;
    public readonly CreateDoctor $createDoctor;
    public readonly GetDoctorAppointmentSettings $getDoctorAppointmentSettings;
    public readonly UpdateDoctorAppointmentSettings $updateDoctorAppointmentSettings;
    public readonly MaterializeBookableSlots $materializeBookableSlots;
    public readonly MaterializeBookableSlotsForAllDoctors $materializeBookableSlotsForAllDoctors;
    public readonly CreatePatient $createPatient;
    public readonly ListBookableAppointments $listBookableAppointments;
    public readonly ListAuditLogs $listAuditLogs;
    public readonly HttpActionRunner $httpActionRunner;
    private readonly AuditLogPort $auditLog;

    public static function fromConfig(array $config, bool $useInMemory = false): self
    {
        $doctors               = new InMemoryDoctorAdapter();
        $patients              = new InMemoryPatientAdapter();
        $auditLog              = new InMemoryAuditLogAdapter();
        $appointmentSettings   = new InMemoryDoctorAppointmentSettingsAdapter();
        $bookableSlots         = new InMemoryBookableSlotAdapter();
        $horizonDays           = (int) ($config['bookable_slot_horizon_days'] ?? 15);
        $bookableSlotGenerator = new BookableSlotGenerator(
            ClinicLunchBreakFromConfig::fromConfigArray($config)->lunchBreak(),
        );

        if ($useInMemory) {
            $scheduling    = new InMemorySchedulingAdapter();
            $prescriptions = new InMemoryPrescriptionAdapter();

            return new self(
                bookableSlotCommands       : $bookableSlots,
                bookableSlotQueries        : $bookableSlots,
                schedulingCommands         : $scheduling,
                schedulingQueries          : $scheduling,
                expirationQueue            : new InMemoryExpirationQueueAdapter(),
                prescriptionCommands       : $prescriptions,
                prescriptionQueries        : $prescriptions,
                doctorCommands             : $doctors,
                doctorQueries              : $doctors,
                appointmentSettingsCommands: $appointmentSettings,
                appointmentSettingsQueries : $appointmentSettings,
                patientCommands            : $patients,
                patientQueries             : $patients,
                auditLog                   : $auditLog,
                clock                      : new SystemClock(),
                horizon                    : new FixedBookableSlotHorizon($horizonDays),
                bookableSlotGenerator      : $bookableSlotGenerator,
            );
        }

        $redis      = RedisClientFactory::fromDsn($config['redis_dsn']);
        $scheduling = new RedisSchedulingAdapter(
            redis               : $redis,
            slotsKeyPrefix      : $config['slots_key_prefix'],
            appointmentKeyPrefix: $config['appointment_key_prefix'],
        );
        $prescriptions = new RedisPrescriptionAdapter(
            redis    : $redis,
            keyPrefix: $config['prescription_key_prefix'],
        );

        return new self(
            bookableSlotCommands: $bookableSlots,
            bookableSlotQueries : $bookableSlots,
            schedulingCommands  : $scheduling,
            schedulingQueries   : $scheduling,
            expirationQueue     : new RedisExpirationQueueAdapter(
                redis           : $redis,
                zsetKey         : $config['expiration_zset_key'],
                payloadKeyPrefix: $config['expiration_payload_prefix'],
            ),
            prescriptionCommands     : $prescriptions,
            prescriptionQueries      : $prescriptions,
            doctorCommands           : $doctors,
            doctorQueries            : $doctors,
            appointmentSettingsCommands: $appointmentSettings,
            appointmentSettingsQueries : $appointmentSettings,
            patientCommands          : $patients,
            patientQueries           : $patients,
            auditLog                 : $auditLog,
            clock                    : new SystemClock(),
            horizon                  : new FixedBookableSlotHorizon($horizonDays),
            bookableSlotGenerator    : $bookableSlotGenerator,
        );
    }

    private function __construct(
        BookableSlotCommandPort $bookableSlotCommands,
        BookableSlotQueryPort $bookableSlotQueries,
        SchedulingCommandPort $schedulingCommands,
        SchedulingQueryPort $schedulingQueries,
        ExpirationQueuePort $expirationQueue,
        PrescriptionCommandPort $prescriptionCommands,
        PrescriptionQueryPort $prescriptionQueries,
        DoctorCommandPort $doctorCommands,
        DoctorQueryPort $doctorQueries,
        DoctorAppointmentSettingsCommandPort $appointmentSettingsCommands,
        DoctorAppointmentSettingsQueryPort $appointmentSettingsQueries,
        PatientCommandPort $patientCommands,
        PatientQueryPort $patientQueries,
        AuditLogPort $auditLog,
        ClockPort $clock,
        BookableSlotHorizonPort $horizon,
        BookableSlotGenerator $bookableSlotGenerator,
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
        $this->publishBookableSlots = new PublishBookableSlots($bookableSlotCommands, $doctorQueries);
        $this->cancelAppointmentHold = new CancelAppointmentHold(
            $schedulingCommands,
            $schedulingQueries,
            $bookableSlotCommands,
            $expirationQueue,
        );
        $this->holdAppointment = new HoldAppointment(
            scheduling          : $schedulingCommands,
            bookableSlotCommands: $bookableSlotCommands,
            bookableSlotQueries : $bookableSlotQueries,
            expirationQueue     : $expirationQueue,
            doctors             : $doctorQueries,
            patients            : $patientQueries,
        );
        $this->confirmAppointment = new ConfirmAppointment(
            $schedulingCommands,
            $schedulingQueries,
            $bookableSlotCommands,
            $expirationQueue,
        );
        $this->processExpiredItems = new ProcessExpiredItems(
            expirationQueue      : $expirationQueue,
            cancelAppointmentHold: $this->cancelAppointmentHold,
            clock                : $clock,
        );

        $this->createPrescription = new CreatePrescription($prescriptionCommands);
        $this->getPrescription = new GetPrescription($prescriptionQueries);
        $this->updatePrescription = new UpdatePrescription($prescriptionCommands, $prescriptionQueries);

        $this->materializeBookableSlots = new MaterializeBookableSlots(
            bookableSlots      : $bookableSlotCommands,
            appointmentSettings: $appointmentSettingsQueries,
            doctors            : $doctorQueries,
            horizon            : $horizon,
            clock              : $clock,
            generator          : $bookableSlotGenerator,
        );
        $this->materializeBookableSlotsForAllDoctors = new MaterializeBookableSlotsForAllDoctors(
            doctors                 : $doctorQueries,
            appointmentSettings     : $appointmentSettingsQueries,
            materializeBookableSlots: $this->materializeBookableSlots,
        );
        $this->createDoctor = new CreateDoctor(
            doctors            : $doctorCommands,
            appointmentSettings: $appointmentSettingsCommands,
        );
        $this->getDoctorAppointmentSettings = new GetDoctorAppointmentSettings(
            settings: $appointmentSettingsQueries,
            doctors : $doctorQueries,
        );
        $this->updateDoctorAppointmentSettings = new UpdateDoctorAppointmentSettings(
            settingsCommands        : $appointmentSettingsCommands,
            doctors                 : $doctorQueries,
            materializeBookableSlots: $this->materializeBookableSlots,
        );
        $this->createPatient = new CreatePatient($patientCommands);
        $this->listBookableAppointments = new ListBookableAppointments($doctorQueries, $bookableSlotQueries);
        $this->listAuditLogs = new ListAuditLogs($auditLog);

        $auditListener       = new RecordAuditLogListener($auditLog);
        $exceptionDispatcher = new SyncEventDispatcher(
            exceptionListeners    : [
                new DoctorNotFoundExceptionListener(),
                new DoctorAppointmentSettingsNotFoundExceptionListener(),
                new PatientNotFoundExceptionListener(),
                new PrescriptionNotFoundExceptionListener(),
                new AppointmentNotFoundExceptionListener(),
                new NoSlotsAvailableExceptionListener(),
                new BookableSlotNotFoundExceptionListener(),
                new BookableSlotUnavailableExceptionListener(),
                new OverlappingBookableWindowExceptionListener(),
                new ConcurrentUpdateExceptionListener(),
                new UnauthorizedPrescriptionChangeExceptionListener(),
            ],
            actionAuditedListeners: [$auditListener],
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
