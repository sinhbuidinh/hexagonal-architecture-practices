<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Http;

use HexagonPractise\Application\Audit\AuditActions;
use HexagonPractise\Application\Audit\AuditLogListScope;
use HexagonPractise\Application\Audit\AuditRequestContext;
use HexagonPractise\Bootstrap\Container;

final class AuditLogController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(string $method, string $path): array
    {
        if ($method !== 'GET' || ! preg_match('#^/audit-logs/(.+)$#', $path, $matches)) {
            return ['status' => 404, 'error' => 'Not found'];
        }

        $auditAction = rawurldecode($matches[1]);
        if (! AuditActions::isKnown($auditAction)) {
            return ['status' => 404, 'error' => 'Not found'];
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $audit = AuditRequestContext::fromHttpHints(['actor_role' => 'Auditor']);

        return $this->container->httpActionRunner->run(
            fn () => ['data' => $this->container->listAuditLogs->execute(
                $limit,
                $auditAction,
                AuditLogListScope::unrestricted(),
            )],
            AuditActions::AUDIT_LIST,
            $audit,
        );
    }
}
