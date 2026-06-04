<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Audit;

/** Stable action names for audit_log.action. */
final class AuditActions
{
    public const DOCTOR_CREATE                      = 'doctor.create';
    public const DOCTOR_APPOINTMENT_SETTINGS_GET    = 'doctor.appointment_settings.get';
    public const DOCTOR_APPOINTMENT_SETTINGS_UPDATE = 'doctor.appointment_settings.update';
    public const PATIENT_CREATE                     = 'patient.create';
    public const APPOINTMENTS_LIST_BOOKABLE         = 'appointments.list_bookable';
    public const AVAILABILITY_SET                   = 'availability.set';
    public const APPOINTMENT_HOLD                   = 'appointment.hold';
    public const APPOINTMENT_CANCEL                 = 'appointment.cancel';
    public const APPOINTMENT_CONFIRM                = 'appointment.confirm';
    public const EXPIRATION_PROCESS                 = 'expiration.process';
    public const PRESCRIPTION_CREATE                = 'prescription.create';
    public const PRESCRIPTION_GET                   = 'prescription.get';
    public const PRESCRIPTION_UPDATE                = 'prescription.update';
    public const AUDIT_LIST                         = 'audit.list';
}
