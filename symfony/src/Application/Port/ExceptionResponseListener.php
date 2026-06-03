<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Event\DomainExceptionOccurred;

interface ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void;
}
