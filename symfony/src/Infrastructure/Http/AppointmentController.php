<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Booking\Query\ListBookableAppointments;
use App\Application\Expiration\ProcessExpiredItems;
use App\Application\Scheduling\Command\CancelAppointmentHold;
use App\Application\Scheduling\Command\ConfirmAppointment;
use App\Application\Scheduling\Command\HoldAppointment;
use App\Application\Scheduling\Command\SetPractitionerAvailability;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AppointmentController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly ListBookableAppointments $listBookableAppointments,
        private readonly SetPractitionerAvailability $setPractitionerAvailability,
        private readonly HoldAppointment $holdAppointment,
        private readonly CancelAppointmentHold $cancelAppointmentHold,
        private readonly ConfirmAppointment $confirmAppointment,
        private readonly ProcessExpiredItems $processExpiredItems,
    ) {
    }

    #[Route('/appointments/bookable', name: 'appointments_bookable', methods: ['GET'])]
    public function listBookable(Request $request): JsonResponse
    {
        return $this->httpActionRunner->run(
            fn () => new JsonResponse(['data' => $this->listBookableAppointments->execute()]),
            AuditActions::APPOINTMENTS_LIST_BOOKABLE,
            AuditHttp::merge($request),
        );
    }

    #[Route('/availability', name: 'availability_set', methods: ['POST'])]
    public function setAvailability(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $audit = AuditHttp::merge($request, ['actor_role' => 'Receptionist']);

        return $this->httpActionRunner->run(
            function () use ($data): JsonResponse {
                $this->setPractitionerAvailability->execute(
                    (string) ($data['practitioner_id'] ?? ''),
                    (int) ($data['slots'] ?? 0),
                );

                return new JsonResponse(['message' => 'availability_set']);
            },
            AuditActions::AVAILABILITY_SET,
            $audit,
            beforeState: [
                'practitioner_id' => (string) ($data['practitioner_id'] ?? ''),
                'slots'           => null,
            ],
        );
    }

    #[Route('/appointments', name: 'appointment_hold', methods: ['POST'])]
    public function hold(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $audit = AuditHttp::merge($request, ['actor_role' => 'Patient']);

        return $this->httpActionRunner->run(
            function () use ($data): JsonResponse {
                $result = $this->holdAppointment->execute(
                    (string) ($data['appointment_id'] ?? bin2hex(random_bytes(8))),
                    (string) ($data['practitioner_id'] ?? ''),
                    (string) ($data['patient_id'] ?? ''),
                    (int) ($data['slots'] ?? 1),
                    new \DateTimeImmutable((string) ($data['expires_at'] ?? '+15 minutes')),
                );

                return new JsonResponse(['data' => $result], 201);
            },
            AuditActions::APPOINTMENT_HOLD,
            $audit,
            beforeState: ['status' => 'available'],
        );
    }

    #[Route('/appointments/{appointmentId}/cancel', name: 'appointment_cancel', methods: ['POST'])]
    public function cancel(Request $request, string $appointmentId): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Receptionist']);

        return $this->httpActionRunner->run(
            function () use ($appointmentId): JsonResponse {
                $this->cancelAppointmentHold->execute($appointmentId);

                return new JsonResponse(['message' => 'cancelled']);
            },
            AuditActions::APPOINTMENT_CANCEL,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );
    }

    #[Route('/appointments/{appointmentId}/confirm', name: 'appointment_confirm', methods: ['POST'])]
    public function confirm(Request $request, string $appointmentId): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Receptionist']);

        return $this->httpActionRunner->run(
            function () use ($appointmentId): JsonResponse {
                $this->confirmAppointment->execute($appointmentId);

                return new JsonResponse(['message' => 'confirmed']);
            },
            AuditActions::APPOINTMENT_CONFIRM,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );
    }

    #[Route('/expiration/process', name: 'expiration_process', methods: ['POST'])]
    public function processExpiration(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $limit = (int) ($data['limit'] ?? 100);
        $audit = AuditHttp::merge($request, ['actor_id' => 'system_expiration', 'actor_role' => 'System']);

        return $this->httpActionRunner->run(
            function () use ($limit): JsonResponse {
                $processed = $this->processExpiredItems->execute($limit);

                return new JsonResponse(['processed' => $processed]);
            },
            AuditActions::EXPIRATION_PROCESS,
            $audit,
        );
    }
}
