<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Http;

final readonly class HttpErrorResponse
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public int $status,
        public array $body,
    ) {
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_merge(['status' => $this->status], $this->body);
    }
}
