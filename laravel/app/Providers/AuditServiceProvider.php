<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Audit\Query\ListAuditLogs;
use App\Application\Port\AuditLogPort;
use App\Infrastructure\Persistence\MySql\MySqlAuditLogAdapter;
use Illuminate\Support\ServiceProvider;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MySqlAuditLogAdapter::class);
        $this->app->singleton(AuditLogPort::class, static fn ($app) => $app->make(MySqlAuditLogAdapter::class));
        $this->app->singleton(ListAuditLogs::class);
    }
}
