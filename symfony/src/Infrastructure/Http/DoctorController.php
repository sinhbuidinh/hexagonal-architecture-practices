<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Doctor\Command\CreateDoctor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class DoctorController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly CreateDoctor $createDoctor,
    ) {
    }

    #[Route('/doctors', name: 'doctor_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $doctorId = (string) ($data['doctor_id'] ?? bin2hex(random_bytes(8)));
        $audit = AuditHttp::merge($request, ['doctor_id' => $doctorId, 'actor_role' => 'Physician']);

        return $this->httpActionRunner->run(
            function () use ($data, $doctorId): JsonResponse {
                $result = $this->createDoctor->execute($doctorId, (string) ($data['name'] ?? ''));

                return new JsonResponse(['data' => $result], 201);
            },
            AuditActions::DOCTOR_CREATE,
            $audit,
        );
    }
}
