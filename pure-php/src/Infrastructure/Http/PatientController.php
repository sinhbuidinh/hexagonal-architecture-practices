<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Bootstrap\Container;

final class PatientController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(string $method, string $path, string $body): array
    {
        if ($method !== 'POST' || $path !== '/patients') {
            return ['status' => 404, 'error' => 'Not found'];
        }

        $data = $this->decode($body);
        $patientId = (string) ($data['patient_id'] ?? bin2hex(random_bytes(8)));
        $audit = AuditHttp::contextFrom($data)
            ->withPatientId($patientId)
            ->withActor($patientId, 'Patient');

        return $this->container->httpActionRunner->run(
            function () use ($patientId, $data): array {
                $result = $this->container->createPatient->execute(
                    $patientId,
                    (string) ($data['name'] ?? ''),
                );

                return ['data' => $result];
            },
            AuditActions::PATIENT_CREATE,
            $audit,
            beforeState: null,
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
