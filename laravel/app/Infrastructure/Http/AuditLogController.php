<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Audit\AuditActions;
use App\Application\Audit\AuditLogAccessPolicy;
use App\Application\Audit\Query\ListAuditLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController
{
    public function __construct(
        private readonly HttpActionRunner $httpActionRunner,
        private readonly ListAuditLogs $listAuditLogs,
        private readonly AuditLogAccessPolicy $auditLogAccessPolicy,
    ) {
    }

    public function index(Request $request, string $auditAction): JsonResponse
    {
        $user = AuditHttp::user($request);
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = (int) $request->query('limit', '100');
        $audit = AuditHttp::merge($request);

        return HttpPayload::toJsonResponse($this->httpActionRunner->run(
            function () use ($limit, $auditAction, $user): array {
                $scope = $this->auditLogAccessPolicy->scopeFor($user->role, $user->id);

                return ['data' => $this->listAuditLogs->execute($limit, $auditAction, $scope)];
            },
            AuditActions::AUDIT_LIST,
            $audit,
        ));
    }
}
