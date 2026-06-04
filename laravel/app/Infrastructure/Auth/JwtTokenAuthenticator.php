<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Shared\UserRole;
use App\Models\User;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

final class JwtTokenAuthenticator
{
    public function __construct(private readonly Configuration $jwt)
    {
    }

    public function authenticate(string $jwt): AuthenticatedUser
    {
        try {
            $token = $this->jwt->parser()->parse($jwt);
        } catch (\Throwable) {
            throw new InvalidJwtException('Malformed JWT.');
        }

        if (!$token instanceof UnencryptedToken) {
            throw new InvalidJwtException('Malformed JWT.');
        }

        $this->assertValid($token);

        $userId = $token->claims()->get('sub');
        if (!is_string($userId) && !is_int($userId)) {
            throw new InvalidJwtException('JWT is missing subject.');
        }

        $user = User::query()->find((string) $userId);
        if ($user === null) {
            throw new InvalidJwtException('User not found.');
        }

        try {
            $role = UserRole::fromString((string) $user->role);
        } catch (\InvalidArgumentException) {
            throw new InvalidJwtException('User has an invalid role.');
        }

        return new AuthenticatedUser(
            id   : (string) $user->id,
            name : $user->name,
            email: $user->email,
            role : $role,
        );
    }

    private function assertValid(UnencryptedToken $token): void
    {
        $issuer  = (string) config('jwt.issuer');
        $subject = $token->claims()->get('sub');
        if (!is_string($subject) && !is_int($subject)) {
            throw new InvalidJwtException('JWT is missing subject.');
        }

        $constraints = [
            new SignedWith($this->jwt->signer(), $this->jwt->signingKey()),
            new StrictValidAt(SystemClock::fromSystemTimezone()),
            new IssuedBy($issuer),
            new RelatedTo((string) $subject),
        ];

        try {
            $this->jwt->validator()->assert($token, ...$constraints);
        } catch (RequiredConstraintsViolated) {
            throw new InvalidJwtException('Invalid or expired JWT.');
        }
    }
}
