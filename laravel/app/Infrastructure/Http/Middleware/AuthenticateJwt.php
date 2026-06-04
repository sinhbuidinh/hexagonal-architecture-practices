<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Auth\InvalidJwtException;
use App\Infrastructure\Auth\JwtTokenAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateJwt
{
    public const REQUEST_ATTRIBUTE = 'authenticated_user';

    public function __construct(private readonly JwtTokenAuthenticator $authenticator)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Bearer JWT required.'], 401);
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return response()->json(['message' => 'Bearer JWT required.'], 401);
        }

        try {
            $user = $this->authenticator->authenticate($token);
        } catch (InvalidJwtException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $user);

        return $next($request);
    }
}
