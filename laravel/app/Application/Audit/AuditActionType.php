<?php

declare(strict_types=1);

namespace App\Application\Audit;

/** Maps internal action keys to audit action_type codes. */
final class AuditActionType
{
    public static function fromAction(string $action): string
    {
        return match ($action) {
            AuditActions::DOCTOR_CREATE                      => 'DOCTOR_CREATE',
            AuditActions::DOCTOR_APPOINTMENT_SETTINGS_GET    => 'DOCTOR_APPOINTMENT_SETTINGS_READ',
            AuditActions::DOCTOR_APPOINTMENT_SETTINGS_UPDATE => 'DOCTOR_APPOINTMENT_SETTINGS_UPDATE',
            AuditActions::PATIENT_CREATE             => 'PATIENT_CREATE',
            AuditActions::APPOINTMENTS_LIST_BOOKABLE => 'APPOINTMENTS_LIST_BOOKABLE',
            AuditActions::AVAILABILITY_SET           => 'AVAILABILITY_SET',
            AuditActions::APPOINTMENT_HOLD           => 'APPOINTMENT_HOLD',
            AuditActions::APPOINTMENT_CANCEL         => 'APPOINTMENT_CANCEL',
            AuditActions::APPOINTMENT_CONFIRM        => 'APPOINTMENT_CONFIRM',
            AuditActions::EXPIRATION_PROCESS         => 'EXPIRATION_PROCESS',
            AuditActions::PRESCRIPTION_CREATE        => 'PRESCRIPTION_CREATE',
            AuditActions::PRESCRIPTION_GET           => 'PRESCRIPTION_READ',
            AuditActions::PRESCRIPTION_UPDATE        => 'PRESCRIPTION_UPDATE',
            AuditActions::AUDIT_LIST                 => 'AUDIT_LOG_READ',
            default                                  => strtoupper(str_replace(['.', '-'], '_', $action)),
        };
    }
}
