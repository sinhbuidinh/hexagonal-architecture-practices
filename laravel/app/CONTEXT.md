# laravel/app — hexagon layers

Identical structure to `pure-php/src/` except namespace `App\` and no `Bootstrap/`.

```
Domain/
  Shared/          PractitionerId, PatientId, AppointmentId, PrescriptionId, SlotCount, ActorRole
  Scheduling/      AppointmentHold, exceptions
  Prescription/    Prescription, PrescriptionStatus, exceptions
  Expiration/      ExpiringItem
Application/
  Port/            *CommandPort, *QueryPort, ExpirationQueuePort, ClockPort (CQRS)
  Doctor/Command/       CreateDoctor
  Patient/Command/      CreatePatient
  Booking/Query/        ListBookableAppointments
  Scheduling/Command/   4 write use cases
  Prescription/Command/ Create, Update
  Prescription/Query/   Get
  Expiration/      ProcessExpiredItems
Infrastructure/
  Http/            Doctor, Patient, Appointment, Prescription controllers
  Persistence/MySql/    Doctor, Patient, Scheduling, Expiration, Prescription, Audit adapters
  Persistence/InMemory/   legacy in-memory adapters (not wired by default)
  Clock/           SystemClock, FrozenClock
Providers/         DI; EventServiceProvider (exceptions + audit events), AuditServiceProvider
```

## HTTP → use case

| Controller | Provider |
|------------|----------|
| `DoctorController`, `PatientController` | `CatalogServiceProvider` |
| `AppointmentController` | `AppointmentServiceProvider` |
| `PrescriptionController` | `PrescriptionServiceProvider` |

Deep reference: `pure-php/src/Application/CONTEXT.md`, `pure-php/src/Domain/CONTEXT.md`.
