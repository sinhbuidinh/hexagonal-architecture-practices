<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Doctor\Command\CreateDoctor;
use App\Application\Doctor\Command\UpdateDoctorAppointmentSettings;
use App\Application\Doctor\Query\GetDoctorAppointmentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DoctorController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreateDoctor $createDoctor,
        private readonly GetDoctorAppointmentSettings $getDoctorAppointmentSettings,
        private readonly UpdateDoctorAppointmentSettings $updateDoctorAppointmentSettings,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            action: function () use ($request): array {
                $data = $this->createDoctor->execute(
                    name                : (string) $request->input('name', ''),
                    userId              : $request->filled('user_id') ? (int) $request->input('user_id') : null,
                    specialties         : is_array($request->input('specialties')) ? $request->input('specialties') : [],
                    languages           : is_array($request->input('languages')) ? $request->input('languages') : [],
                    licenseNumber       : $request->filled('license_number') ? (string) $request->input('license_number') : null,
                    acceptingNewPatients: (bool) $request->input('accepting_new_patients', true),
                );

                return ['data' => $data];
            },
            auditAction  : AuditActions::DOCTOR_CREATE,
            auditRequest : $audit,
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function getAppointmentSettings(Request $request, int $doctorId): JsonResponse
    {
        return HttpPayload::toJsonResponse($this->httpActionRunner->run(
            action      : fn (): array => ['data' => $this->getDoctorAppointmentSettings->execute($doctorId)],
            auditAction : AuditActions::DOCTOR_APPOINTMENT_SETTINGS_GET,
            auditRequest: AuditHttp::merge($request),
        ));
    }

    public function updateAppointmentSettings(Request $request, int $doctorId): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            action: function () use ($request, $doctorId): array {
                $weekly = $request->input('weekly_schedule');
                if (!is_array($weekly)) {
                    throw new \InvalidArgumentException('weekly_schedule must be an array.');
                }

                return [
                    'data' => $this->updateDoctorAppointmentSettings->execute(
                        practitionerId     : $doctorId,
                        slotDurationMinutes: (int) $request->input('slot_duration_minutes', 15),
                        weeklySchedule     : $weekly,
                        timezone           : $request->filled('timezone')
                            ? (string) $request->input('timezone')
                            : null,
                    ),
                ];
            },
            auditAction : AuditActions::DOCTOR_APPOINTMENT_SETTINGS_UPDATE,
            auditRequest: $audit,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
