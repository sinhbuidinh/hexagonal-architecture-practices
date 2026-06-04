<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Prescription\Command\CreatePrescription;
use App\Application\Prescription\Command\UpdatePrescription;
use App\Application\Prescription\Query\GetPrescription;
use App\Domain\Prescription\PrescriptionNotFoundException;
use App\Domain\Shared\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PrescriptionController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreatePrescription $createPrescription,
        private readonly GetPrescription $getPrescription,
        private readonly UpdatePrescription $updatePrescription,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $user = AuditHttp::user($request);
        if ($user === null || $user->role !== UserRole::DOCTOR) {
            return response()->json(['message' => 'Only doctors may create prescriptions.'], 403);
        }

        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            function () use ($request): array {
                $data = $this->createPrescription->execute(
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
        $audit = AuditHttp::merge($request);
        try {
            $rx    = $this->getPrescription->execute($prescriptionId);
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
        $user = AuditHttp::user($request);
        if ($user === null || !in_array($user->role, [UserRole::DOCTOR, UserRole::PHARMACIST], true)) {
            return response()->json(['message' => 'Only doctors and pharmacists may update prescriptions.'], 403);
        }

        $actorRole = $user->role->toActorRole()->value;
        $audit     = AuditHttp::merge($request);

        $beforeState = null;
        try {
            $beforeState = $this->getPrescription->execute($prescriptionId);
            $audit       = $audit->withPatientId((string) ($beforeState['patient_id'] ?? null));
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
