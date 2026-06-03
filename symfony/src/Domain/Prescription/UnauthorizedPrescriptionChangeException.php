<?php

declare(strict_types=1);

namespace App\Domain\Prescription;

use App\Domain\Shared\ActorRole;

final class UnauthorizedPrescriptionChangeException extends \DomainException
{
    public function __construct(ActorRole $actor, string $field)
    {
        parent::__construct(sprintf('Role "%s" may not update field "%s".', $actor->value, $field));
    }
}
