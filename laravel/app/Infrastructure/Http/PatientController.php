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
        $audit = AuditHttp::merge($request);

        $payload = $this->httpActionRunner->run(
            action: function () use ($request): array {
                $data = $this->createPatient->execute(
                    name             : (string) $request->input('name', ''),
                    userId           : $request->filled('user_id') ? (int) $request->input('user_id') : null,
                    preferredLanguage: $request->filled('preferred_language') ? (string) $request->input('preferred_language') : null,
                    dateOfBirth      : $request->filled('date_of_birth') ? (string) $request->input('date_of_birth') : null,
                    phone            : $request->filled('phone') ? (string) $request->input('phone') : null,
                );

                return ['data' => $data];
            },
            auditAction  : AuditActions::PATIENT_CREATE,
            auditRequest : $audit,
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
