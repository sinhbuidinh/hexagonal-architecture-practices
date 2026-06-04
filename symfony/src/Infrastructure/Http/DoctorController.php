<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Doctor\Command\CreateDoctor;
use App\Application\Doctor\Command\UpdateDoctorAppointmentSettings;
use App\Application\Doctor\Query\GetDoctorAppointmentSettings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class DoctorController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreateDoctor $createDoctor,
        private readonly GetDoctorAppointmentSettings $getDoctorAppointmentSettings,
        private readonly UpdateDoctorAppointmentSettings $updateDoctorAppointmentSettings,
    ) {
    }

    #[Route('/doctors', name: 'doctor_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data     = $request->toArray();
        $doctorId = (int) ($data['doctor_id'] ?? random_int(1, 999_999));
        $audit    = AuditHttp::merge($request, ['doctor_id' => $doctorId, 'actor_role' => 'Physician']);

        return $this->httpActionRunner->run(
            function () use ($data, $doctorId): JsonResponse {
                $result = $this->createDoctor->execute(
                    $doctorId,
                    (string) ($data['name'] ?? ''),
                    is_array($data['specialties'] ?? null) ? $data['specialties'] : [],
                    is_array($data['languages'] ?? null) ? $data['languages'] : [],
                    isset($data['license_number']) ? (string) $data['license_number'] : null,
                    (bool) ($data['accepting_new_patients'] ?? true),
                    isset($data['user_id']) ? (int) $data['user_id'] : null,
                );

                return new JsonResponse(['data' => $result], 201);
            },
            AuditActions::DOCTOR_CREATE,
            $audit,
        );
    }

    #[Route('/doctors/{doctorId}/appointment-settings', name: 'doctor_appointment_settings_get', methods: ['GET'])]
    public function getAppointmentSettings(Request $request, int $doctorId): JsonResponse
    {
        return $this->httpActionRunner->run(
            fn (): JsonResponse => new JsonResponse([
                'data' => $this->getDoctorAppointmentSettings->execute($doctorId),
            ]),
            AuditActions::DOCTOR_APPOINTMENT_SETTINGS_GET,
            AuditHttp::merge($request),
        );
    }

    #[Route('/doctors/{doctorId}/appointment-settings', name: 'doctor_appointment_settings_update', methods: ['PUT'])]
    public function updateAppointmentSettings(Request $request, int $doctorId): JsonResponse
    {
        $data  = $request->toArray();
        $audit = AuditHttp::merge($request);

        return $this->httpActionRunner->run(
            function () use ($data, $doctorId): JsonResponse {
                $weekly = $data['weekly_schedule'] ?? null;
                if (!is_array($weekly)) {
                    throw new \InvalidArgumentException('weekly_schedule must be an object.');
                }

                return new JsonResponse([
                    'data' => $this->updateDoctorAppointmentSettings->execute(
                        practitionerId     : $doctorId,
                        slotDurationMinutes: (int) ($data['slot_duration_minutes'] ?? 15),
                        weeklySchedule     : $weekly,
                        timezone           : isset($data['timezone']) ? (string) $data['timezone'] : null,
                    ),
                ]);
            },
            AuditActions::DOCTOR_APPOINTMENT_SETTINGS_UPDATE,
            $audit,
        );
    }
}
