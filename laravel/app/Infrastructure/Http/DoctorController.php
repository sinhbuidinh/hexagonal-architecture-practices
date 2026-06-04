<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Doctor\Command\CreateDoctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DoctorController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreateDoctor $createDoctor,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $doctorId = (string) $request->input('doctor_id', bin2hex(random_bytes(8)));
        $audit    = AuditHttp::merge($request, [
            'doctor_id'  => $doctorId,
            'actor_role' => 'Physician',
        ]);

        $payload = $this->httpActionRunner->run(
            function () use ($request, $doctorId): array {
                $data = $this->createDoctor->execute(
                    $doctorId,
                    (string) $request->input('name', ''),
                );

                return ['data' => $data];
            },
            AuditActions::DOCTOR_CREATE,
            $audit,
            successStatus: 201,
        );

        return HttpPayload::toJsonResponse($payload);
    }
}
