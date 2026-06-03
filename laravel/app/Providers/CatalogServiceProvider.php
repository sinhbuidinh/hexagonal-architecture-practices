<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Booking\Query\ListBookableAppointments;
use App\Application\Doctor\Command\CreateDoctor;
use App\Application\Patient\Command\CreatePatient;
use App\Application\Port\DoctorCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Application\Port\PatientCommandPort;
use App\Application\Port\PatientQueryPort;
use App\Infrastructure\Persistence\InMemory\InMemoryDoctorAdapter;
use App\Infrastructure\Persistence\InMemory\InMemoryPatientAdapter;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryDoctorAdapter::class);
        $this->app->singleton(DoctorCommandPort::class, static fn ($app) => $app->make(InMemoryDoctorAdapter::class));
        $this->app->singleton(DoctorQueryPort::class, static fn ($app) => $app->make(InMemoryDoctorAdapter::class));

        $this->app->singleton(InMemoryPatientAdapter::class);
        $this->app->singleton(PatientCommandPort::class, static fn ($app) => $app->make(InMemoryPatientAdapter::class));
        $this->app->singleton(PatientQueryPort::class, static fn ($app) => $app->make(InMemoryPatientAdapter::class));

        $this->app->singleton(CreateDoctor::class);
        $this->app->singleton(CreatePatient::class);
        $this->app->singleton(ListBookableAppointments::class);
    }
}
