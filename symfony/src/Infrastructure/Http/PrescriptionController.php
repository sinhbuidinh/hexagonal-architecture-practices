<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Prescription\Command\CreatePrescription;
use App\Application\Prescription\Command\UpdatePrescription;
use App\Application\Prescription\Query\GetPrescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class PrescriptionController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreatePrescription $createPrescription,
        private readonly GetPrescription $getPrescription,
        private readonly UpdatePrescription $updatePrescription,
    ) {
    }

    #[Route('/prescriptions', name: 'prescription_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Physician']);

        return $this->httpActionRunner->run(
            function () use ($request): JsonResponse {
                $data   = $request->toArray();
                $result = $this->createPrescription->execute(
                    (string) ($data['prescription_id'] ?? bin2hex(random_bytes(8))),
                    (string) ($data['patient_id'] ?? ''),
                    (string) ($data['medication'] ?? ''),
                    (string) ($data['dosage'] ?? ''),
                    (string) ($data['instructions'] ?? ''),
                );

                return new JsonResponse(['data' => $result], 201);
            },
            AuditActions::PRESCRIPTION_CREATE,
            $audit,
        );
    }

    #[Route('/prescriptions/{prescriptionId}', name: 'prescription_show', methods: ['GET'])]
    public function show(Request $request, string $prescriptionId): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Physician']);
        try {
            $rx    = $this->getPrescription->execute($prescriptionId);
            $audit = $audit->withPatientId((string) ($rx['patient_id'] ?? null));
        } catch (PrescriptionNotFoundException) {
        }

        return $this->httpActionRunner->run(
            fn () => new JsonResponse(['data' => $this->getPrescription->execute($prescriptionId)]),
            AuditActions::PRESCRIPTION_GET,
            $audit,
        );
    }

    #[Route('/prescriptions/{prescriptionId}', name: 'prescription_update', methods: ['PUT'])]
    public function update(Request $request, string $prescriptionId): JsonResponse
    {
        $data      = $request->toArray();
        $actorRole = (string) ($data['actor_role'] ?? $data['actor'] ?? 'Physician');
        $audit     = AuditHttp::merge($request, ['actor_role' => $actorRole]);

        $beforeState = null;
        try {
            $beforeState = $this->getPrescription->execute($prescriptionId);
            $audit       = $audit->withPatientId((string) ($beforeState['patient_id'] ?? null));
        } catch (PrescriptionNotFoundException) {
        }

        return $this->httpActionRunner->run(
            function () use ($data, $prescriptionId, $actorRole): JsonResponse {
                $result = $this->updatePrescription->execute(
                    $prescriptionId,
                    (int) ($data['expected_version'] ?? 0),
                    $actorRole,
                    array_intersect_key($data, array_flip([
                        'medication', 'dosage', 'instructions', 'status', 'pharmacy_notes',
                    ])),
                );

                return new JsonResponse(['data' => $result]);
            },
            AuditActions::PRESCRIPTION_UPDATE,
            $audit,
            beforeState: $beforeState,
        );
    }
}
