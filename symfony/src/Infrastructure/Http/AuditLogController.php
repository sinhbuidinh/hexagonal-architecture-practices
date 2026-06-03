<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Audit\Query\ListAuditLogs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuditLogController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly ListAuditLogs $listAuditLogs,
    ) {
    }

    #[Route('/audit-logs', name: 'audit_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 100);
        $audit = AuditHttp::merge($request, ['actor_role' => 'Auditor']);

        return $this->httpActionRunner->run(
            fn () => new JsonResponse(['data' => $this->listAuditLogs->execute($limit)]),
            AuditActions::AUDIT_LIST,
            $audit,
        );
    }
}
