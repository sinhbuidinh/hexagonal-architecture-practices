<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Application\Audit\AuditRequestContext;
use HexagonPractise\Bootstrap\Container;

final class AppointmentController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(string $method, string $path, string $body): array
    {
        $runner = $this->container->httpActionRunner;

        return match (true) {
            $method === 'GET' && $path === '/appointments/bookable' => $runner->run(
                fn () => ['data' => $this->container->listBookableAppointments->execute()],
                AuditActions::APPOINTMENTS_LIST_BOOKABLE,
                AuditRequestContext::fromHttpHints(),
            ),
            $method === 'POST' && $path === '/availability' => $this->setAvailability($runner, $body),
            $method === 'POST' && $path === '/appointments' => $this->hold($runner, $body),
            $method === 'POST' && preg_match('#^/appointments/([^/]+)/cancel$#', $path, $m) === 1
                => $this->cancel($runner, $m[1], $body),
            $method === 'POST' && preg_match('#^/appointments/([^/]+)/confirm$#', $path, $m) === 1
                                                                  => $this->confirm($runner, $m[1], $body),
            $method === 'POST' && $path === '/expiration/process' => $this->processExpiration($runner, $body),
            default                                               => ['status' => 404, 'error' => 'Not found'],
        };
    }

    /** @return array<string, mixed> */
    private function setAvailability(HttpActionRunner $runner, string $body): array
    {
        $data = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? $data['practitioner_id'] ?? 'system'),
            (string) ($data['actor_role'] ?? 'Receptionist'),
        );

        return $runner->run(
            function () use ($data): array {
                $this->container->setPractitionerAvailability->execute(
                    (string) ($data['practitioner_id'] ?? ''),
                    (int) ($data['slots'] ?? 0),
                );

                return ['message' => 'availability_set'];
            },
            AuditActions::AVAILABILITY_SET,
            $audit,
            beforeState: ['slots' => null, 'practitioner_id' => (string) ($data['practitioner_id'] ?? '')],
        );
    }

    /** @return array<string, mixed> */
    private function hold(HttpActionRunner $runner, string $body): array
    {
        $data = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? $data['patient_id'] ?? 'system'),
            (string) ($data['actor_role'] ?? 'Patient'),
        );

        return $runner->run(
            function () use ($data): array {
                $result = $this->container->holdAppointment->execute(
                    (string) ($data['appointment_id'] ?? bin2hex(random_bytes(8))),
                    (string) ($data['practitioner_id'] ?? ''),
                    (string) ($data['patient_id'] ?? ''),
                    (int) ($data['slots'] ?? 1),
                    new \DateTimeImmutable((string) ($data['expires_at'] ?? '+15 minutes')),
                );

                return ['data' => $result];
            },
            AuditActions::APPOINTMENT_HOLD,
            $audit,
            beforeState: ['status' => 'available'],
            successStatus: 201,
        );
    }

    /** @return array<string, mixed> */
    private function cancel(HttpActionRunner $runner, string $appointmentId, string $body): array
    {
        $data = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? 'system'),
            (string) ($data['actor_role'] ?? 'Receptionist'),
        );

        return $runner->run(
            function () use ($appointmentId): array {
                $this->container->cancelAppointmentHold->execute($appointmentId);

                return ['message' => 'cancelled'];
            },
            AuditActions::APPOINTMENT_CANCEL,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );
    }

    /** @return array<string, mixed> */
    private function confirm(HttpActionRunner $runner, string $appointmentId, string $body): array
    {
        $data = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? 'system'),
            (string) ($data['actor_role'] ?? 'Receptionist'),
        );

        return $runner->run(
            function () use ($appointmentId): array {
                $this->container->confirmAppointment->execute($appointmentId);

                return ['message' => 'confirmed'];
            },
            AuditActions::APPOINTMENT_CONFIRM,
            $audit,
            beforeState: ['status' => 'held', 'appointment_id' => $appointmentId],
        );
    }

    /** @return array<string, mixed> */
    private function processExpiration(HttpActionRunner $runner, string $body): array
    {
        $data = $this->decode($body);
        $limit = (int) ($data['limit'] ?? 100);
        $audit = AuditHttp::contextFrom($data)->withActor('system_expiration', 'System');

        return $runner->run(
            function () use ($limit): array {
                $processed = $this->container->processExpiredItems->execute($limit);

                return ['processed' => $processed];
            },
            AuditActions::EXPIRATION_PROCESS,
            $audit,
        );
    }

    /** @return array<string, mixed> */
    private function decode(string $body): array
    {
        if ($body === '') {
            return [];
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
