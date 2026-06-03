<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Expiration\ProcessExpiredItems;
use App\Application\Port\ClockPort;
use App\Application\Port\ExpirationQueuePort;
use App\Application\Port\SchedulingCommandPort;
use App\Application\Port\SchedulingQueryPort;
use App\Application\Scheduling\Command\CancelAppointmentHold;
use App\Application\Scheduling\Command\ConfirmAppointment;
use App\Application\Scheduling\Command\HoldAppointment;
use App\Application\Scheduling\Command\SetPractitionerAvailability;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Persistence\InMemory\InMemoryExpirationQueueAdapter;
use App\Infrastructure\Persistence\InMemory\InMemorySchedulingAdapter;
use Illuminate\Support\ServiceProvider;

final class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemorySchedulingAdapter::class);
        $this->app->singleton(SchedulingCommandPort::class, static fn ($app) => $app->make(InMemorySchedulingAdapter::class));
        $this->app->singleton(SchedulingQueryPort::class, static fn ($app) => $app->make(InMemorySchedulingAdapter::class));

        $this->app->singleton(ExpirationQueuePort::class, InMemoryExpirationQueueAdapter::class);
        $this->app->singleton(ClockPort::class, SystemClock::class);

        $this->app->singleton(SetPractitionerAvailability::class);
        $this->app->singleton(HoldAppointment::class);
        $this->app->singleton(CancelAppointmentHold::class);
        $this->app->singleton(ConfirmAppointment::class);
        $this->app->singleton(ProcessExpiredItems::class);
    }
}
