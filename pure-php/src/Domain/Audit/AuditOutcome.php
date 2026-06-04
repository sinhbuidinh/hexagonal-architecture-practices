<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Audit;

enum AuditOutcome: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
}
