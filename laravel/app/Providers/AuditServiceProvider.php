<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Audit\Query\ListAuditLogs;
use App\Application\Port\AuditLogPort;
use App\Infrastructure\Persistence\InMemory\InMemoryAuditLogAdapter;
use Illuminate\Support\ServiceProvider;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryAuditLogAdapter::class);
        $this->app->singleton(AuditLogPort::class, static fn ($app) => $app->make(InMemoryAuditLogAdapter::class));
        $this->app->singleton(ListAuditLogs::class);
    }
}
