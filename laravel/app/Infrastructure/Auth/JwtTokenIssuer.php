<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Models\User;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;

final class JwtTokenIssuer
{
    public function __construct(private readonly Configuration $jwt)
    {
    }

    public function issueForUser(User $user, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $ttl = (int) config('jwt.ttl_seconds');

        return $this->jwt->builder()
            ->issuedBy((string) config('jwt.issuer'))
            ->relatedTo((string) $user->id)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify(sprintf('+%d seconds', $ttl)))
            ->getToken($this->jwt->signer(), $this->jwt->signingKey())
            ->toString();
    }
}
