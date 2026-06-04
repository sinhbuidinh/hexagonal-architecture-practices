<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Bootstrap\Container;
use HexagonPractise\Domain\Prescription\PrescriptionNotFoundException;

final class PrescriptionController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(string $method, string $path, string $body): array
    {
        $runner = $this->container->httpActionRunner;

        return match (true) {
            $method === 'POST' && $path === '/prescriptions' => $this->create($runner, $body),
            $method === 'GET' && preg_match('#^/prescriptions/([^/]+)$#', $path, $m) === 1
                => $this->get($runner, $m[1], $body),
            $method === 'PUT' && preg_match('#^/prescriptions/([^/]+)$#', $path, $m) === 1
                    => $this->update($runner, $m[1], $body),
            default => ['status' => 404, 'error' => 'Not found'],
        };
    }

    /** @return array<string, mixed> */
    private function create(HttpActionRunner $runner, string $body): array
    {
        $data  = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? $data['actor'] ?? 'system'),
            (string) ($data['actor_role'] ?? $data['actor'] ?? 'Physician'),
        );

        return $runner->run(
            function () use ($data): array {
                $result = $this->container->createPrescription->execute(
                    (string) ($data['prescription_id'] ?? bin2hex(random_bytes(8))),
                    (string) ($data['patient_id'] ?? ''),
                    (string) ($data['medication'] ?? ''),
                    (string) ($data['dosage'] ?? ''),
                    (string) ($data['instructions'] ?? ''),
                );

                return ['data' => $result];
            },
            AuditActions::PRESCRIPTION_CREATE,
            $audit,
            beforeState  : null,
            successStatus: 201,
        );
    }

    /** @return array<string, mixed> */
    private function get(HttpActionRunner $runner, string $prescriptionId, string $body): array
    {
        $data  = $this->decode($body);
        $audit = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? $data['actor'] ?? 'system'),
            (string) ($data['actor_role'] ?? $data['actor'] ?? 'Physician'),
        );

        return $runner->run(
            function () use ($prescriptionId): array {
                return ['data' => $this->container->getPrescription->execute($prescriptionId)];
            },
            AuditActions::PRESCRIPTION_GET,
            $audit->withPatientId($this->resolvePatientIdForPrescription($prescriptionId)),
        );
    }

    /** @return array<string, mixed> */
    private function update(HttpActionRunner $runner, string $prescriptionId, string $body): array
    {
        $data      = $this->decode($body);
        $actorRole = (string) ($data['actor_role'] ?? $data['actor'] ?? 'Physician');
        $audit     = AuditHttp::contextFrom($data)->withActor(
            (string) ($data['actor_id'] ?? $data['actor'] ?? 'system'),
            $actorRole,
        );

        $beforeState = null;
        try {
            $beforeState = $this->container->getPrescription->execute($prescriptionId);
            $audit       = $audit->withPatientId((string) ($beforeState['patient_id'] ?? null));
        } catch (PrescriptionNotFoundException) {
            // logged as failure when use case throws
        }

        return $runner->run(
            function () use ($prescriptionId, $data, $actorRole): array {
                $result = $this->container->updatePrescription->execute(
                    $prescriptionId,
                    (int) ($data['expected_version'] ?? 0),
                    $actorRole,
                    array_intersect_key($data, array_flip([
                        'medication', 'dosage', 'instructions', 'status', 'pharmacy_notes',
                    ])),
                );

                return ['data' => $result];
            },
            AuditActions::PRESCRIPTION_UPDATE,
            $audit,
            beforeState: $beforeState,
        );
    }

    /** @return array<string, mixed> */
    private function resolvePatientIdForPrescription(string $prescriptionId): ?string
    {
        try {
            $rx = $this->container->getPrescription->execute($prescriptionId);

            return (string) ($rx['patient_id'] ?? null);
        } catch (PrescriptionNotFoundException) {
            return null;
        }
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
