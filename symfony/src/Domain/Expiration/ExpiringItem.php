<?php

declare(strict_types=1);

namespace App\Domain\Expiration;

/**
 * An item scheduled for processing when its expiration time is reached.
 */
final readonly class ExpiringItem
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public array $payload,
        public \DateTimeImmutable $expiresAt,
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('ExpiringItem id cannot be empty.');
        }
    }

    public function isDue(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
