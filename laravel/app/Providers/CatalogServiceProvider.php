<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Booking\Query\ListBookableAppointments;
use App\Application\Doctor\Command\CreateDoctor;
use App\Application\Doctor\Command\UpdateDoctorAppointmentSettings;
use App\Application\Doctor\Query\GetDoctorAppointmentSettings;
use App\Application\Patient\Command\CreatePatient;
use App\Application\Port\DoctorCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Application\Port\PatientCommandPort;
use App\Application\Port\PatientQueryPort;
use App\Infrastructure\Persistence\MySql\MySqlDoctorAdapter;
use App\Infrastructure\Persistence\MySql\MySqlPatientAdapter;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MySqlDoctorAdapter::class);
        $this->app->singleton(DoctorCommandPort::class, static fn ($app) => $app->make(MySqlDoctorAdapter::class));
        $this->app->singleton(DoctorQueryPort::class, static fn ($app) => $app->make(MySqlDoctorAdapter::class));

        $this->app->singleton(MySqlPatientAdapter::class);
        $this->app->singleton(PatientCommandPort::class, static fn ($app) => $app->make(MySqlPatientAdapter::class));
        $this->app->singleton(PatientQueryPort::class, static fn ($app) => $app->make(MySqlPatientAdapter::class));

        $this->app->singleton(CreateDoctor::class);
        $this->app->singleton(GetDoctorAppointmentSettings::class);
        $this->app->singleton(UpdateDoctorAppointmentSettings::class);
        $this->app->singleton(CreatePatient::class);
        $this->app->singleton(ListBookableAppointments::class);
    }
}
