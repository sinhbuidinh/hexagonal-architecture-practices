<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Port\AuditLogPort;
use App\Application\Port\ClockPort;
use App\Application\Port\EventDispatcherPort;
use App\Infrastructure\Event\DomainExceptionHandler;
use App\Infrastructure\Event\Listener\AppointmentNotFoundExceptionListener;
use App\Infrastructure\Event\Listener\ConcurrentUpdateExceptionListener;
use App\Infrastructure\Event\Listener\DoctorNotFoundExceptionListener;
use App\Infrastructure\Event\Listener\NoSlotsAvailableExceptionListener;
use App\Infrastructure\Event\Listener\PatientNotFoundExceptionListener;
use App\Infrastructure\Event\Listener\PrescriptionNotFoundExceptionListener;
use App\Infrastructure\Event\Listener\RecordAuditLogListener;
use App\Infrastructure\Event\Listener\UnauthorizedPrescriptionChangeExceptionListener;
use App\Infrastructure\Event\SyncEventDispatcher;
use App\Infrastructure\Http\HttpActionRunner;
use Illuminate\Support\ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventDispatcherPort::class, static function ($app): SyncEventDispatcher {
            return new SyncEventDispatcher(
                [
                    new DoctorNotFoundExceptionListener(),
                    new PatientNotFoundExceptionListener(),
                    new PrescriptionNotFoundExceptionListener(),
                    new AppointmentNotFoundExceptionListener(),
                    new NoSlotsAvailableExceptionListener(),
                    new ConcurrentUpdateExceptionListener(),
                    new UnauthorizedPrescriptionChangeExceptionListener(),
                ],
                [new RecordAuditLogListener($app->make(AuditLogPort::class))],
            );
        });

        $this->app->singleton(DomainExceptionHandler::class, static fn ($app) => new DomainExceptionHandler(
            $app->make(EventDispatcherPort::class),
            $app->make(ClockPort::class),
        ));

        $this->app->singleton(HttpActionRunner::class, static fn ($app) => new HttpActionRunner(
            $app->make(DomainExceptionHandler::class),
            $app->make(EventDispatcherPort::class),
            $app->make(ClockPort::class),
        ));
    }
}
