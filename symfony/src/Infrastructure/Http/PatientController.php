<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Patient\Command\CreatePatient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class PatientController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreatePatient $createPatient,
    ) {
    }

    #[Route('/patients', name: 'patient_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data      = $request->toArray();
        $patientId = (string) ($data['patient_id'] ?? bin2hex(random_bytes(8)));
        $audit     = AuditHttp::merge($request, ['patient_id' => $patientId, 'actor_role' => 'Patient']);

        return $this->httpActionRunner->run(
            function () use ($data, $patientId): JsonResponse {
                $result = $this->createPatient->execute(
                    $patientId,
                    (string) ($data['name'] ?? ''),
                    isset($data['preferred_language']) ? (string) $data['preferred_language'] : null,
                    isset($data['date_of_birth']) ? (string) $data['date_of_birth'] : null,
                    isset($data['phone']) ? (string) $data['phone'] : null,
                    isset($data['user_id']) ? (int) $data['user_id'] : null,
                );

                return new JsonResponse(['data' => $result], 201);
            },
            AuditActions::PATIENT_CREATE,
            $audit,
        );
    }
}
