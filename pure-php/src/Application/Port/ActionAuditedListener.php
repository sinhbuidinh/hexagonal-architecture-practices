<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Application\Event\ActionAudited;

interface ActionAuditedListener
{
    public function onActionAudited(ActionAudited $event): void;
}
