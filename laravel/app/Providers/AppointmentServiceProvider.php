<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Expiration\ProcessExpiredItems;
use App\Application\Port\BookableSlotCommandPort;
use App\Application\Port\BookableSlotHorizonPort;
use App\Application\Port\BookableSlotQueryPort;
use App\Application\Port\ClinicLunchBreakPort;
use App\Application\Port\ClockPort;
use App\Application\Port\DoctorAppointmentSettingsCommandPort;
use App\Application\Port\DoctorAppointmentSettingsQueryPort;
use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\SchedulingCommandPort;
use App\Application\Port\SchedulingQueryPort;
use App\Application\Scheduling\Command\CancelAppointmentHold;
use App\Application\Scheduling\Command\ConfirmAppointment;
use App\Application\Scheduling\Command\HoldAppointment;
use App\Application\Scheduling\Command\MaterializeBookableSlots;
use App\Application\Scheduling\Command\MaterializeBookableSlotsForAllDoctors;
use App\Application\Scheduling\Command\PublishBookableSlots;
use App\Application\Scheduling\Command\SetPractitionerAvailability;
use App\Domain\Scheduling\BookableSlotGenerator;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Persistence\MySql\MySqlBookableSlotAdapter;
use App\Infrastructure\Persistence\MySql\MySqlDoctorAppointmentSettingsAdapter;
use App\Infrastructure\Persistence\MySql\MySqlExpirationQueueAdapter;
use App\Infrastructure\Persistence\MySql\MySqlSchedulingAdapter;
use App\Infrastructure\Scheduling\BookableSlotHorizonFromConfig;
use App\Infrastructure\Scheduling\ClinicLunchBreakFromConfig;
use Illuminate\Support\ServiceProvider;

final class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MySqlSchedulingAdapter::class);
        $this->app->singleton(MySqlBookableSlotAdapter::class);
        $this->app->singleton(MySqlDoctorAppointmentSettingsAdapter::class);
        $this->app->singleton(DoctorAppointmentSettingsCommandPort::class, static fn ($app) => $app->make(MySqlDoctorAppointmentSettingsAdapter::class));
        $this->app->singleton(DoctorAppointmentSettingsQueryPort::class, static fn ($app) => $app->make(MySqlDoctorAppointmentSettingsAdapter::class));
        $this->app->singleton(BookableSlotHorizonPort::class, BookableSlotHorizonFromConfig::class);
        $this->app->singleton(ClinicLunchBreakPort::class, ClinicLunchBreakFromConfig::class);
        $this->app->singleton(BookableSlotGenerator::class, static function ($app): BookableSlotGenerator {
            return new BookableSlotGenerator($app->make(ClinicLunchBreakPort::class)->lunchBreak());
        });
        $this->app->singleton(SchedulingCommandPort::class, static fn ($app) => $app->make(MySqlSchedulingAdapter::class));
        $this->app->singleton(SchedulingQueryPort::class, static fn ($app) => $app->make(MySqlSchedulingAdapter::class));
        $this->app->singleton(BookableSlotCommandPort::class, static fn ($app) => $app->make(MySqlBookableSlotAdapter::class));
        $this->app->singleton(BookableSlotQueryPort::class, static fn ($app) => $app->make(MySqlBookableSlotAdapter::class));

        $this->app->singleton(ExpirationQueuePort::class, MySqlExpirationQueueAdapter::class);
        $this->app->singleton(ClockPort::class, SystemClock::class);

        $this->app->singleton(SetPractitionerAvailability::class);
        $this->app->singleton(PublishBookableSlots::class);
        $this->app->singleton(MaterializeBookableSlots::class);
        $this->app->singleton(MaterializeBookableSlotsForAllDoctors::class);
        $this->app->singleton(HoldAppointment::class);
        $this->app->singleton(CancelAppointmentHold::class);
        $this->app->singleton(ConfirmAppointment::class);
        $this->app->singleton(ProcessExpiredItems::class);
    }
}
