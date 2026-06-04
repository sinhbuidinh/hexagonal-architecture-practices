<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Audit\AuditActionType;
use App\Application\Audit\AuditActions;
use App\Application\Booking\Query\ListBookableAppointments;
use App\Application\Doctor\Command\CreateDoctor;
use App\Application\Doctor\Command\UpdateDoctorAppointmentSettings;
use App\Application\Patient\Command\CreatePatient;
use App\Application\Scheduling\Command\HoldAppointment;
use App\Domain\Audit\AuditOutcome;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            ['name' => 'Dr Alice', 'email' => 'doctor@example.com', 'role' => 'doctor', 'secret' => 'doctor-dev-secret'],
            ['name' => 'Receptionist Minh', 'email' => 'reception@example.com', 'role' => 'receptionist', 'secret' => 'reception-dev-secret'],
            ['name' => 'Pharmacist Bob', 'email' => 'pharmacist@example.com', 'role' => 'pharmacist', 'secret' => 'pharmacist-dev-secret'],
            ['name' => 'Patient Carol', 'email' => 'patient@example.com', 'role' => 'patient', 'secret' => 'patient-dev-secret'],
        ];

        $createdUsers = [];

        foreach ($users as $spec) {
            $user = User::query()->create([
                'name'     => $spec['name'],
                'email'    => $spec['email'],
                'password' => Hash::make('password'),
                'role'     => $spec['role'],
            ]);

            $token = $user->id . '.' . $spec['secret'];

            ApiToken::query()->create([
                'user_id' => $user->id,
                'token'   => $token,
            ]);

            $createdUsers[$spec['role']] = $user;

            $this->command?->info(sprintf('%s bearer token: %s', $spec['role'], $token));
        }

        $createDoctor   = app(CreateDoctor::class);
        $updateSettings = app(UpdateDoctorAppointmentSettings::class);
        $createPatient  = app(CreatePatient::class);

        $drAlice = $createDoctor->execute(
            name                : 'Dr Alice Nguyen',
            userId              : $createdUsers['doctor']->id,
            specialties         : ['General Practice', 'Internal Medicine'],
            languages           : ['English', 'Vietnamese'],
            licenseNumber       : 'MD-10001',
            acceptingNewPatients: true,
        );

        $drNeurology = $createDoctor->execute(
            name                : 'Dr Tan — Neurology',
            userId              : null,
            specialties         : ['Neurology', 'Khám Thần Kinh'],
            languages           : ['English', 'Vietnamese'],
            licenseNumber       : 'MD-NEURO-02',
            acceptingNewPatients: true,
        );

        $updateSettings->execute(
            practitionerId     : $drNeurology['doctor_id'],
            slotDurationMinutes: 60,
            weeklySchedule     : $this->clinicWeeklySchedule(),
            timezone           : 'Asia/Ho_Chi_Minh',
        );

        $drAllDayClinic = $createDoctor->execute(
            name                : 'Dr Park — All-day clinic',
            userId              : null,
            specialties         : ['Family Medicine'],
            languages           : ['English', 'Vietnamese'],
            licenseNumber       : 'MD-ALLDAY-04',
            acceptingNewPatients: true,
        );

        $updateSettings->execute(
            practitionerId     : $drAllDayClinic['doctor_id'],
            slotDurationMinutes: 30,
            weeklySchedule     : $this->clinicWeeklySchedule(),
            timezone           : 'Asia/Ho_Chi_Minh',
        );

        $drBusy = $createDoctor->execute(
            name                : 'Dr Lee (not accepting)',
            userId              : null,
            specialties         : ['Dermatology'],
            languages           : ['English'],
            licenseNumber       : 'MD-10003',
            acceptingNewPatients: false,
        );

        $patientCarol = $createPatient->execute(
            name             : 'Patient Carol',
            userId           : $createdUsers['patient']->id,
            preferredLanguage: 'Vietnamese',
            dateOfBirth      : '1990-05-15',
            phone            : '+84-555-0100',
        );

        $createPatient->execute(
            name             : 'Walk-in Dependent',
            userId           : null,
            preferredLanguage: 'English',
            dateOfBirth      : '2018-03-22',
            phone            : null,
        );

        $bookableForNeuro = app(ListBookableAppointments::class)->execute($drNeurology['doctor_id']);
        $sampleHold       = null;

        if ($bookableForNeuro !== []) {
            $firstSlot  = $bookableForNeuro[0];
            $sampleHold = app(HoldAppointment::class)->execute(
                practitionerId: $drNeurology['doctor_id'],
                patientId     : $patientCarol['patient_id'],
                bookableSlotId: $firstSlot['slot_id'],
                expiresAt     : new \DateTimeImmutable('+15 minutes'),
            );
        }

        $this->seedAuditLogs(
            actorReceptionId: (string) $createdUsers['receptionist']->id,
            actorPatientId  : (string) $createdUsers['patient']->id,
            patientProfileId: $patientCarol['patient_id'],
            doctors         : [$drAlice, $drNeurology, $drAllDayClinic, $drBusy],
            sampleHold      : $sampleHold,
        );

        $this->printApiGuide(
            tokens: [
                'patient'      => $createdUsers['patient']->id . '.patient-dev-secret',
                'doctor'       => $createdUsers['doctor']->id . '.doctor-dev-secret',
                'receptionist' => $createdUsers['receptionist']->id . '.reception-dev-secret',
            ],
            doctors: [
                'dr_alice'      => $drAlice,
                'dr_neurology'  => $drNeurology,
                'dr_all_day'    => $drAllDayClinic,
                'dr_busy'       => $drBusy,
            ],
            patientId      : $patientCarol['patient_id'],
            bookableSample : $bookableForNeuro[0] ?? null,
            sampleHold     : $sampleHold,
        );
    }

    /**
     * Mon–Thu 09:00–15:30; Fri afternoon; Sat morning; Sun off.
     *
     * @return array<string, array{start_time: string, end_time: string}|null>
     */
    private function clinicWeeklySchedule(): array
    {
        $allDay    = static fn (): array => ['start_time' => '09:00', 'end_time' => '15:30'];
        $morning   = static fn (): array => ['start_time' => '07:30', 'end_time' => '11:30'];
        $afternoon = static fn (): array => ['start_time' => '13:30', 'end_time' => '15:30'];

        return [
            'mon' => $allDay(),
            'tue' => $allDay(),
            'wed' => $allDay(),
            'thu' => $allDay(),
            'fri' => $afternoon(),
            'sat' => $morning(),
            'sun' => null,
        ];
    }

    /**
     * @param list<array{doctor_id: int, name: string, specialties: list<string>}> $doctors
     * @param array<string, mixed>|null $sampleHold
     */
    private function seedAuditLogs(
        string $actorReceptionId,
        string $actorPatientId,
        string $patientProfileId,
        array $doctors,
        ?array $sampleHold,
    ): void {
        $at = now()->subHours(2);

        $rows = [
            [
                'action'       => AuditActions::DOCTOR_CREATE,
                'outcome'      => AuditOutcome::SUCCESS->value,
                'occurred_at'  => $at,
                'actor_id'     => $actorReceptionId,
                'actor_role'   => 'Receptionist',
                'patient_id'   => null,
                'action_type'  => AuditActionType::fromAction(AuditActions::DOCTOR_CREATE),
                'ip_address'   => '127.0.0.1',
                'device_id'    => 'seed-cli',
                'after_state'  => json_encode(['doctor_id' => $doctors[0]['doctor_id'], 'name' => $doctors[0]['name']], JSON_THROW_ON_ERROR),
                'http_status'  => 201,
            ],
            [
                'action'       => AuditActions::DOCTOR_APPOINTMENT_SETTINGS_UPDATE,
                'outcome'      => AuditOutcome::SUCCESS->value,
                'occurred_at'  => $at->copy()->addMinutes(5),
                'actor_id'     => $actorReceptionId,
                'actor_role'   => 'Receptionist',
                'patient_id'   => null,
                'action_type'  => AuditActionType::fromAction(AuditActions::DOCTOR_APPOINTMENT_SETTINGS_UPDATE),
                'ip_address'   => '127.0.0.1',
                'device_id'    => 'seed-cli',
                'after_state'  => json_encode(['doctor_id' => $doctors[1]['doctor_id'], 'timezone' => 'Asia/Ho_Chi_Minh'], JSON_THROW_ON_ERROR),
                'http_status'  => 200,
            ],
            [
                'action'       => AuditActions::PATIENT_CREATE,
                'outcome'      => AuditOutcome::SUCCESS->value,
                'occurred_at'  => $at->copy()->addMinutes(10),
                'actor_id'     => $actorReceptionId,
                'actor_role'   => 'Receptionist',
                'patient_id'   => $patientProfileId,
                'action_type'  => AuditActionType::fromAction(AuditActions::PATIENT_CREATE),
                'ip_address'   => '127.0.0.1',
                'device_id'    => 'seed-cli',
                'after_state'  => json_encode(['patient_id' => $patientProfileId], JSON_THROW_ON_ERROR),
                'http_status'  => 201,
            ],
            [
                'action'       => AuditActions::APPOINTMENTS_LIST_BOOKABLE,
                'outcome'      => AuditOutcome::SUCCESS->value,
                'occurred_at'  => $at->copy()->addMinutes(20),
                'actor_id'     => $actorPatientId,
                'actor_role'   => 'Patient',
                'patient_id'   => $patientProfileId,
                'action_type'  => AuditActionType::fromAction(AuditActions::APPOINTMENTS_LIST_BOOKABLE),
                'ip_address'   => '127.0.0.1',
                'device_id'    => 'seed-mobile',
                'after_state'  => json_encode(['doctor_id' => $doctors[1]['doctor_id']], JSON_THROW_ON_ERROR),
                'http_status'  => 200,
            ],
        ];

        if ($sampleHold !== null) {
            $rows[] = [
                'action'       => AuditActions::APPOINTMENT_HOLD,
                'outcome'      => AuditOutcome::SUCCESS->value,
                'occurred_at'  => $at->copy()->addMinutes(25),
                'actor_id'     => $actorPatientId,
                'actor_role'   => 'Patient',
                'patient_id'   => $patientProfileId,
                'action_type'  => AuditActionType::fromAction(AuditActions::APPOINTMENT_HOLD),
                'ip_address'   => '127.0.0.1',
                'device_id'    => 'seed-mobile',
                'before_state' => json_encode(['status' => 'available'], JSON_THROW_ON_ERROR),
                'after_state'  => json_encode($sampleHold, JSON_THROW_ON_ERROR),
                'http_status'  => 201,
            ];
        }

        $rows[] = [
            'action'       => AuditActions::AUDIT_LIST,
            'outcome'      => AuditOutcome::SUCCESS->value,
            'occurred_at'  => $at->copy()->addMinutes(30),
            'actor_id'     => $actorReceptionId,
            'actor_role'   => 'Receptionist',
            'patient_id'   => null,
            'action_type'  => AuditActionType::fromAction(AuditActions::AUDIT_LIST),
            'ip_address'   => '127.0.0.1',
            'device_id'    => 'seed-cli',
            'http_status'  => 200,
        ];

        foreach ($rows as $row) {
            DB::table('audit_logs')->insert([
                'action'            => $row['action'],
                'outcome'           => $row['outcome'],
                'occurred_at'       => $row['occurred_at'],
                'actor_id'          => $row['actor_id'],
                'actor_role'        => $row['actor_role'],
                'patient_id'        => $row['patient_id'],
                'action_type'       => $row['action_type'],
                'ip_address'        => $row['ip_address'],
                'device_id'         => $row['device_id'],
                'before_state'      => $row['before_state'] ?? null,
                'after_state'       => $row['after_state'] ?? null,
                'state_diff'        => null,
                'exception_class'   => null,
                'exception_message' => null,
                'http_status'       => $row['http_status'],
            ]);
        }
    }

    /**
     * @param array{patient: string, doctor: string, receptionist: string} $tokens
     * @param array{dr_alice: array{doctor_id: int, name: string, specialties: list<string>}, dr_neurology: array{doctor_id: int, name: string, specialties: list<string>}, dr_all_day: array{doctor_id: int, name: string, specialties: list<string>}, dr_busy: array{doctor_id: int, name: string, specialties: list<string>}> $doctors
     * @param array<string, mixed>|null $bookableSample
     * @param array<string, mixed>|null $sampleHold
     */
    private function printApiGuide(
        array $tokens,
        array $doctors,
        string $patientId,
        ?array $bookableSample,
        ?array $sampleHold,
    ): void {
        if ($this->command === null) {
            return;
        }

        $base = 'http://127.0.0.1:8000/api';
        $auth = static fn (string $token): string => "Authorization: Bearer {$token}";

        $this->command->newLine();
        $this->command->info('--- Seeded doctors (use doctor_id in URLs) ---');
        foreach ($doctors as $key => $doctor) {
            $this->command->line(sprintf(
                '  %s: id=%d | %s | %s',
                $key,
                $doctor['doctor_id'],
                $doctor['name'],
                implode(', ', $doctor['specialties']),
            ));
        }

        $neuroId   = $doctors['dr_neurology']['doctor_id'];
        $allDayId  = $doctors['dr_all_day']['doctor_id'];

        $this->command->newLine();
        $this->command->info('--- Try the booking flow (newest: date/time slots) ---');
        $this->command->line("  List bookable (neurology, 60-min): GET {$base}/appointments/bookable/{$neuroId}");
        $this->command->line("  List bookable (all-day clinic, 30-min): GET {$base}/appointments/bookable/{$allDayId}");
        $this->command->line("    Header: {$auth($tokens['patient'])}");

        if ($bookableSample !== null) {
            $this->command->line(sprintf(
                '    Example slot: #%d %s %s–%s',
                $bookableSample['slot_id'],
                $bookableSample['date'],
                $bookableSample['start_time'],
                $bookableSample['end_time'],
            ));
        }

        $this->command->line("  Hold appointment: POST {$base}/appointments");
        $this->command->line('    Body: practitioner_id, patient_id, bookable_slot_id, expires_at');

        if ($sampleHold !== null) {
            $this->command->line(sprintf(
                '    Seeded hold: appointment_id=%s (slot #%d)',
                $sampleHold['appointment_id'],
                $sampleHold['bookable_slot_id'],
            ));
        }

        $this->command->newLine();
        $this->command->info('--- Audit trail ---');
        $this->command->line("  GET {$base}/audit-logs?limit=50");
        $this->command->line("    Header: {$auth($tokens['receptionist'])}");

        $this->command->newLine();
        $this->command->info('--- Settings (optional) ---');
        $this->command->line("  GET {$base}/doctors/{$neuroId}/appointment-settings");

        $this->command->newLine();
        $this->command->comment('Patient profile id for holds: ' . $patientId);
        $this->command->comment('Dr Alice: default UTC 15-min. Dr Neurology: 60-min clinic schedule. Dr Park (all-day): same weekly hours, 30-min slots (Asia/Ho_Chi_Minh).');
    }
}
