# symfony/src — hexagon layers

Mirror of `laravel/app/` and `pure-php/src/`. Namespace `App\`.

```
Domain/              # no services — excluded in services.yaml
Application/         # use cases + ports (autowired)
Infrastructure/
  Http/              AppointmentController, PrescriptionController  #[Route('/api/...')]
  Persistence/InMemory/
  Clock/
Kernel.php           # framework entry (not hexagon)
```

## Routes (attributes)

| Controller | Prefix |
|------------|--------|
| `AppointmentController` | `/api` + `/availability`, `/appointments`, … |
| `PrescriptionController` | `/api/prescriptions` |

## DI

Explicit port bindings only in `config/services_*.yaml`. Everything else autowired.

Logic details: `pure-php/src/Application/CONTEXT.md`, `pure-php/src/Infrastructure/CONTEXT.md`.
