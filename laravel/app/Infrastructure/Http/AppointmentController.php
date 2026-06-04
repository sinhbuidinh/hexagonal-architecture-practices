<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Booking\Query\ListBookableAppointments;
use App\Application\Expiration\ProcessExpiredItems;
use App\Application\Scheduling\Command\CancelAppointmentHold;
use App\Application\Scheduling\Command\ConfirmAppointment;
use App\Application\Scheduling\Command\HoldAppointment;
use App\Application\Scheduling\Command\PublishBookableSlots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AppointmentController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly ListBookableAppointments $listBookableAppointments,
        private readonly PublishBookableSlots $publishBookableSlots,
        private readonly HoldAppointment $holdAppointment,
        private readonly CancelAppointmentHold $cancelAppointmentHold,
        private readonly ConfirmAppointment $confirmAppointment,
        private readonly ProcessExpiredItems $processExpiredItems,
    ) {
    }

    public function listBookable(Request $request, int $doctorId): JsonResponse
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        return HttpPayload::toJsonResponse($this->httpActionRunner->run(
            fn (): array => ['data' => $this->listBookableAppointments->execute(
                $doctorId,
                is_string($dateFrom) ? $dateFrom : null,
                is_string($dateTo) ? $dateTo : null,
            )],
            AuditActions::APPOINTMENTS_LIST_BOOKABLE,
            AuditHttp::merge($request),
        ));
    }

    public function setAvailability(Request $request): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            function () use ($request): array {
                $published = $this->publishBookableSlots->execute(
                    (int) $request->input('practitioner_id', 0),
                    (array) $request->input('time_slots', []),
                );

                return ['message' => 'availability_set', 'data' => $published];
            },
            AuditActions::AVAILABILITY_SET,
            $audit,
            beforeState: [
                'practitioner_id' => (int) $request->input('practitioner_id', 0),
            ],
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function hold(Request $request): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            function () use ($request): array {
                $data = $this->holdAppointment->execute(
                    (int) $request->input('practitioner_id', 0),
                    (string) $request->input('patient_id', ''),
                    (int) $request->input('bookable_slot_id', 0),
                    new \DateTimeImmutable((string) $request->input('expires_at', '+15 minutes')),
                );

                return ['data' => $data];
            },
            AuditActions::APPOINTMENT_HOLD,
            $audit,
            beforeState  : ['status' => 'available'],
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function cancel(Request $request, string $appointmentId): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            function () use ($appointmentId): array {
                $this->cancelAppointmentHold->execute($appointmentId);

                return ['message' => 'cancelled'];
            },
            AuditActions::APPOINTMENT_CANCEL,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function confirm(Request $request, string $appointmentId): JsonResponse
    {
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            function () use ($appointmentId): array {
                $this->confirmAppointment->execute($appointmentId);

                return ['message' => 'confirmed'];
            },
            AuditActions::APPOINTMENT_CONFIRM,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function processExpiration(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 100);
        $audit = AuditHttp::merge($request, [
            'actor_id'   => 'system_expiration',
            'actor_role' => 'System',
        ]);

        $payload = $this->httpActionRunner->run(
            function () use ($limit): array {
                $processed = $this->processExpiredItems->execute($limit);

                return ['processed' => $processed];
            },
            AuditActions::EXPIRATION_PROCESS,
            $audit,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
