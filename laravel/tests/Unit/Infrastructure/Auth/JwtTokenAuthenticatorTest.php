<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Auth;

use App\Infrastructure\Auth\InvalidJwtException;
use App\Infrastructure\Auth\JwtTokenAuthenticator;
use App\Infrastructure\Auth\JwtTokenIssuer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class JwtTokenAuthenticatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticates_valid_jwt_for_existing_user(): void
    {
        $user = User::factory()->create(['role' => 'patient']);

        $jwt      = app(JwtTokenIssuer::class)->issueForUser($user);
        $authUser = app(JwtTokenAuthenticator::class)->authenticate($jwt);

        $this->assertSame((string) $user->id, $authUser->id);
        $this->assertSame('patient', $authUser->role->value);
    }

    public function test_rejects_tampered_jwt(): void
    {
        $user = User::factory()->create();
        $jwt  = app(JwtTokenIssuer::class)->issueForUser($user);

        $this->expectException(InvalidJwtException::class);
        app(JwtTokenAuthenticator::class)->authenticate($jwt . 'x');
    }
}
