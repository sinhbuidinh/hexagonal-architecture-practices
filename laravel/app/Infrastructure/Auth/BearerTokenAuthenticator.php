<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Shared\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class BearerTokenAuthenticator
{
    public function authenticate(string $bearerToken): AuthenticatedUser
    {
        $parts = explode('.', $bearerToken, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidBearerTokenException('Malformed bearer token.');
        }

        $userId = $parts[0];

        $tokenExists = DB::table('api_tokens')
            ->where(column: 'user_id', operator: '=', value: $userId)
            ->where(column: 'token', operator: '=', value: $bearerToken)
            ->exists();

        if (!$tokenExists) {
            throw new InvalidBearerTokenException('Invalid or revoked bearer token.');
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            throw new InvalidBearerTokenException('User not found.');
        }

        try {
            $role = UserRole::fromString((string) $user->role);
        } catch (\InvalidArgumentException) {
            throw new InvalidBearerTokenException('User has an invalid role.');
        }

        return new AuthenticatedUser(
            id   : (string) $user->id,
            name : $user->name,
            email: $user->email,
            role : $role,
        );
    }
}
