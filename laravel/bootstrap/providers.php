<?php

use App\Providers\AppServiceProvider;
use App\Providers\AppointmentServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\CatalogServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\PrescriptionServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AuditServiceProvider::class,
    EventServiceProvider::class,
    CatalogServiceProvider::class,
    AppointmentServiceProvider::class,
    PrescriptionServiceProvider::class,
];
