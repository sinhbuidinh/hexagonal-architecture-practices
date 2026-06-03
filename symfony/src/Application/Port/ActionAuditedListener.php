<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Event\ActionAudited;

interface ActionAuditedListener
{
    public function onActionAudited(ActionAudited $event): void;
}
