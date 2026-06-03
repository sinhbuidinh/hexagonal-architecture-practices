<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Port;

use HexagonPractise\Application\Event\DomainExceptionOccurred;

interface ExceptionResponseListener
{
    public function onDomainExceptionOccurred(DomainExceptionOccurred $event): void;
}
