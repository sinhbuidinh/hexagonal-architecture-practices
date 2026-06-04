<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Auth\JwtConfiguration;
use App\Infrastructure\Auth\JwtTokenAuthenticator;
use App\Infrastructure\Auth\JwtTokenIssuer;
use Illuminate\Support\ServiceProvider;
use Lcobucci\JWT\Configuration;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Configuration::class, static fn (): Configuration => JwtConfiguration::make());
        $this->app->singleton(JwtTokenIssuer::class);
        $this->app->singleton(JwtTokenAuthenticator::class);
    }
}
