<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Patient\Command\CreatePatient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PatientController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreatePatient $createPatient,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $patientId = (string) $request->input('patient_id', bin2hex(random_bytes(8)));
        $audit     = AuditHttp::merge($request, [
            'patient_id' => $patientId,
            'actor_role' => 'Patient',
        ]);

        $payload   = $this->httpActionRunner->run(
            function () use ($request, $patientId): array {
                $data = $this->createPatient->execute(
                    $patientId,
                    (string) $request->input('name', ''),
                );

                return ['data' => $data];
            },
            AuditActions::PATIENT_CREATE,
            $audit,
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
