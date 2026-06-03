<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Port\PrescriptionCommandPort;
use App\Application\Port\PrescriptionQueryPort;
use App\Application\Prescription\Command\CreatePrescription;
use App\Application\Prescription\Command\UpdatePrescription;
use App\Application\Prescription\Query\GetPrescription;
use App\Infrastructure\Persistence\InMemory\InMemoryPrescriptionAdapter;
use Illuminate\Support\ServiceProvider;

final class PrescriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryPrescriptionAdapter::class);
        $this->app->singleton(PrescriptionCommandPort::class, static fn ($app) => $app->make(InMemoryPrescriptionAdapter::class));
        $this->app->singleton(PrescriptionQueryPort::class, static fn ($app) => $app->make(InMemoryPrescriptionAdapter::class));

        $this->app->singleton(CreatePrescription::class);
        $this->app->singleton(GetPrescription::class);
        $this->app->singleton(UpdatePrescription::class);
    }
}
