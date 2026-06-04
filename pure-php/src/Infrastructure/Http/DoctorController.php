<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Bootstrap\Container;

final class DoctorController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(string $method, string $path, string $body): array
    {
        if ($method !== 'POST' || $path !== '/doctors') {
            return ['status' => 404, 'error' => 'Not found'];
        }

        $data     = $this->decode($body);
        $doctorId = (int) ($data['doctor_id'] ?? random_int(1, 999_999));
        $audit    = AuditHttp::contextFrom($data)->withActor((string) $doctorId, 'Physician');

        return $this->container->httpActionRunner->run(
            function () use ($doctorId, $data): array {
                $result = $this->container->createDoctor->execute(
                    $doctorId,
                    (string) ($data['name'] ?? ''),
                    is_array($data['specialties'] ?? null) ? $data['specialties'] : [],
                    is_array($data['languages'] ?? null) ? $data['languages'] : [],
                    isset($data['license_number']) ? (string) $data['license_number'] : null,
                    (bool) ($data['accepting_new_patients'] ?? true),
                    isset($data['user_id']) ? (int) $data['user_id'] : null,
                );

                return ['data' => $result];
            },
            AuditActions::DOCTOR_CREATE,
            $audit,
            beforeState  : null,
            successStatus: 201,
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
