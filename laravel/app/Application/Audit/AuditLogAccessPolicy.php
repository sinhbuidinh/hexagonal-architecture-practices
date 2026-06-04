<?php

declare(strict_types=1);

namespace App\Application\Audit;

use App\Domain\Audit\AuditLogAccessDeniedException;
use App\Domain\Shared\UserRole;

/**
 * Audit read access (HIPAA-style): compliance/admin sees all; clinicians see only their own actions.
 *
 * @see https://www.hhs.gov/hipaa/for-professionals/security/laws-regulations/index.html — access controls & audit controls
 */
final class AuditLogAccessPolicy
{
    public function scopeFor(UserRole $role, string $userId): AuditLogListScope
    {
        return match ($role) {
            UserRole::ADMIN  => AuditLogListScope::unrestricted(),
            UserRole::DOCTOR => AuditLogListScope::forActor($userId),
            default          => throw new AuditLogAccessDeniedException($role),
        };
    }
}
