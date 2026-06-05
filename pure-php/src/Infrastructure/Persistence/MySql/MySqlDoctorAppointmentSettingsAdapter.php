<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use HexagonPractise\Application\Port\DoctorAppointmentSettingsCommandPort;
use HexagonPractise\Application\Port\DoctorAppointmentSettingsQueryPort;
use HexagonPractise\Domain\Doctor\DoctorAppointmentSettings;
use HexagonPractise\Domain\Shared\PractitionerId;

final class MySqlDoctorAppointmentSettingsAdapter implements DoctorAppointmentSettingsCommandPort, DoctorAppointmentSettingsQueryPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(DoctorAppointmentSettings $settings): void
    {
        $this->connection->update(
            table   : 'doctors',
            data    : [
                'slot_duration_minutes' => $settings->slotDurationMinutes,
                'weekly_schedule'       => json_encode($this->encodeWeekly($settings), JSON_THROW_ON_ERROR),
                'schedule_timezone'     => $settings->timezone,
                'updated_at'            => DatabaseConnectionFactory::now(),
            ],
            criteria: ['id' => $settings->practitionerId->value],
        );
    }

    public function ensureDefaults(PractitionerId $practitionerId): void
    {
        $row = $this->connection->fetchAssociative(
            query : 'SELECT weekly_schedule FROM doctors WHERE id = ?',
            params: [$practitionerId->value],
        );

        if ($row === false || $row['weekly_schedule'] !== null) {
            return;
        }

        $this->save(DoctorAppointmentSettings::defaultFor($practitionerId));
    }

    public function find(PractitionerId $practitionerId): ?DoctorAppointmentSettings
    {
        $row = $this->connection->fetchAssociative(
            query : 'SELECT * FROM doctors WHERE id = ?',
            params: [$practitionerId->value],
        );

        if ($row === false || $row['weekly_schedule'] === null) {
            return null;
        }

        return $this->rowToSettings($row);
    }

    /** @param array<string, mixed> $row */
    private function rowToSettings(array $row): DoctorAppointmentSettings
    {
        $weeklyRaw = is_string($row['weekly_schedule'])
            ? json_decode($row['weekly_schedule'], true, 512, JSON_THROW_ON_ERROR)
            : (array) $row['weekly_schedule'];

        return new DoctorAppointmentSettings(
            practitionerId     : new PractitionerId((int) $row['id']),
            slotDurationMinutes: (int) $row['slot_duration_minutes'],
            weeklySchedule     : DoctorAppointmentSettings::weeklyScheduleFromArray(is_array($weeklyRaw) ? $weeklyRaw : []),
            timezone           : (string) ($row['schedule_timezone'] ?? DoctorAppointmentSettings::DEFAULT_TIMEZONE),
        );
    }

    /** @return array<string, array{start_time: string, end_time: string}|null> */
    private function encodeWeekly(DoctorAppointmentSettings $settings): array
    {
        $encoded = [];
        foreach (DoctorAppointmentSettings::WEEKDAY_KEYS as $key) {
            $hours = $settings->weeklySchedule[$key];
            $encoded[$key] = $hours === null
                ? null
                : ['start_time' => $hours->startTime, 'end_time' => $hours->endTime];
        }

        return $encoded;
    }
}
