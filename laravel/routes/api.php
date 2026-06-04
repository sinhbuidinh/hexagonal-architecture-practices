<?php

declare(strict_types=1);

use App\Application\Audit\AuditActions;
use App\Infrastructure\Http\AppointmentController;
use App\Infrastructure\Http\AuditLogController;
use App\Infrastructure\Http\DoctorController;
use App\Infrastructure\Http\PatientController;
use App\Infrastructure\Http\PrescriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.jwt')->group(function (): void {
    Route::get('/audit-logs/{auditAction}', [AuditLogController::class, 'index'])
        ->where('auditAction', AuditActions::routePattern());

    Route::post('/doctors', [DoctorController::class, 'create']);
    Route::get('/doctors/{doctorId}/appointment-settings', [DoctorController::class, 'getAppointmentSettings']);
    Route::put('/doctors/{doctorId}/appointment-settings', [DoctorController::class, 'updateAppointmentSettings']);
    Route::post('/patients', [PatientController::class, 'create']);

    Route::get('/appointments/bookable/{doctorId}', [AppointmentController::class, 'listBookable']);
    Route::post('/availability', [AppointmentController::class, 'setAvailability']);
    Route::post('/appointments', [AppointmentController::class, 'hold']);
    Route::post('/appointments/{appointmentId}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointmentId}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('/expiration/process', [AppointmentController::class, 'processExpiration']);

    Route::post('/prescriptions', [PrescriptionController::class, 'create']);
    Route::get('/prescriptions/{prescriptionId}', [PrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescriptionId}', [PrescriptionController::class, 'update']);
});
