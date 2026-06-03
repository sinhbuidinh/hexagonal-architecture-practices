<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Audit\Query\ListAuditLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly ListAuditLogs $listAuditLogs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 100);
        $audit = AuditHttp::merge($request, ['actor_role' => 'Auditor']);

        return HttpPayload::toJsonResponse($this->httpActionRunner->run(
            fn (): array => ['data' => $this->listAuditLogs->execute($limit)],
            AuditActions::AUDIT_LIST,
            $audit,
        ));
    }
}
