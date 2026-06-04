<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Shared\UserRole;

final class AuditLogAccessDeniedException extends \DomainException
{
    public function __construct(UserRole $role)
    {
        parent::__construct(sprintf('Role "%s" is not permitted to read audit logs.', $role->value));
    }
}
