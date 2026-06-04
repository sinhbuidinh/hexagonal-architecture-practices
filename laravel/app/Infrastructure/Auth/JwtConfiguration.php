<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

final class JwtConfiguration
{
    public static function make(): Configuration
    {
        $secret = (string) config('jwt.secret');
        if ($secret === '') {
            throw new \RuntimeException('JWT secret is not configured (set JWT_SECRET or APP_KEY).');
        }

        $key = str_starts_with($secret, 'base64:')
            ? InMemory::base64Encoded(substr($secret, 7))
            : InMemory::plainText($secret);

        return Configuration::forSymmetricSigner(new Sha256(), $key);
    }
}
