<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Auth\BearerTokenAuthenticator;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BearerTokenAuthenticator::class);
    }
}
