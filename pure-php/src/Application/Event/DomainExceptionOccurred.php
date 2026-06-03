<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Event;

use HexagonPractise\Application\Http\HttpErrorResponse;

/** Dispatched when a use case raises a domain exception; listeners may attach an HTTP response. */
final class DomainExceptionOccurred
{
    public ?HttpErrorResponse $response = null;

    public function __construct(public readonly \Throwable $exception)
    {
    }
}
