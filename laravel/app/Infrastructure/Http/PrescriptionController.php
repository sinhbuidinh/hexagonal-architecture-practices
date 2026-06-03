<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Prescription\Command\CreatePrescription;
use App\Application\Prescription\Command\UpdatePrescription;
use App\Application\Prescription\Query\GetPrescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PrescriptionController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreatePrescription $createPrescription,
        private readonly GetPrescription $getPrescription,
        private readonly UpdatePrescription $updatePrescription,
    ) {}

    public function create(Request $request): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Physician']);

        $payload = $this->httpActionRunner->run(
            function () use ($request): array {
                $data = $this->createPrescription->execute(
                    (string) $request->input('prescription_id', bin2hex(random_bytes(8))),
                    (string) $request->input('patient_id', ''),
                    (string) $request->input('medication', ''),
                    (string) $request->input('dosage', ''),
                    (string) $request->input('instructions', ''),
                );

                return ['data' => $data];
            },
            AuditActions::PRESCRIPTION_CREATE,
            $audit,
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }

    public function show(Request $request, string $prescriptionId): JsonResponse
    {
        $audit = AuditHttp::merge($request, ['actor_role' => 'Physician']);
        try {
            $rx = $this->getPrescription->execute($prescriptionId);
            $audit = $audit->withPatientId((string) ($rx['patient_id'] ?? null));
        } catch (PrescriptionNotFoundException) {
            // failure audited by handler
        }

        return HttpPayload::toJsonResponse($this->httpActionRunner->run(
            fn (): array => ['data' => $this->getPrescription->execute($prescriptionId)],
            AuditActions::PRESCRIPTION_GET,
            $audit,
        ));
    }

    public function update(Request $request, string $prescriptionId): JsonResponse
    {
        $actorRole = (string) ($request->input('actor_role', $request->input('actor', 'Physician')));
        $audit = AuditHttp::merge($request, ['actor_role' => $actorRole]);

        $beforeState = null;
        try {
            $beforeState = $this->getPrescription->execute($prescriptionId);
            $audit = $audit->withPatientId((string) ($beforeState['patient_id'] ?? null));
        } catch (PrescriptionNotFoundException) {
        }

        $payload = $this->httpActionRunner->run(
            function () use ($request, $prescriptionId, $actorRole): array {
                $data = $this->updatePrescription->execute(
                    $prescriptionId,
                    (int) $request->input('expected_version', 0),
                    $actorRole,
                    $request->only(['medication', 'dosage', 'instructions', 'status', 'pharmacy_notes']),
                );

                return ['data' => $data];
            },
            AuditActions::PRESCRIPTION_UPDATE,
            $audit,
            beforeState: $beforeState,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
