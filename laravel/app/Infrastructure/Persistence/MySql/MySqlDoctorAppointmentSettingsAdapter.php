<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\DoctorAppointmentSettingsCommandPort;
use App\Application\Port\DoctorAppointmentSettingsQueryPort;
use App\Domain\Doctor\DailyWorkHours;
use App\Domain\Doctor\DoctorAppointmentSettings;
use App\Domain\Shared\PractitionerId;
use Illuminate\Support\Facades\DB;

final class MySqlDoctorAppointmentSettingsAdapter implements DoctorAppointmentSettingsCommandPort, DoctorAppointmentSettingsQueryPort
{
    public function save(DoctorAppointmentSettings $settings): void
    {
        DB::table('doctors')->where('id', $settings->practitionerId->value)->update([
            'slot_duration_minutes' => $settings->slotDurationMinutes,
            'weekly_schedule'       => json_encode($this->encodeWeekly($settings), JSON_THROW_ON_ERROR),
            'schedule_timezone'     => $settings->timezone,
            'updated_at'            => now(),
        ]);
    }

    public function ensureDefaults(PractitionerId $practitionerId): void
    {
        $row = DB::table('doctors')->where('id', $practitionerId->value)->first();
        if ($row === null) {
            return;
        }

        if ($row->weekly_schedule !== null) {
            return;
        }

        $defaults = DoctorAppointmentSettings::defaultFor($practitionerId);
        $this->save($defaults);
    }

    public function find(PractitionerId $practitionerId): ?DoctorAppointmentSettings
    {
        $row = DB::table('doctors')->where('id', $practitionerId->value)->first();
        if ($row === null) {
            return null;
        }

        if ($row->weekly_schedule === null) {
            return null;
        }

        return $this->rowToSettings($row);
    }

    private function rowToSettings(object $row): DoctorAppointmentSettings
    {
        $weeklyRaw = is_string($row->weekly_schedule)
            ? json_decode($row->weekly_schedule, true, 512, JSON_THROW_ON_ERROR)
            : (array) $row->weekly_schedule;

        return new DoctorAppointmentSettings(
            practitionerId     : new PractitionerId((int) $row->id),
            slotDurationMinutes: (int) $row->slot_duration_minutes,
            weeklySchedule     : DoctorAppointmentSettings::weeklyScheduleFromArray(is_array($weeklyRaw) ? $weeklyRaw : []),
            timezone           : (string) ($row->schedule_timezone ?? DoctorAppointmentSettings::DEFAULT_TIMEZONE),
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
