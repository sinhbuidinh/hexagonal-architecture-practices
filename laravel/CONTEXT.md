# laravel

Same hexagon as `pure-php/`, namespace `App\`, framework = **wiring + HTTP only**.

## Run

```bash
composer install && php artisan serve
# API prefix: /api/...
```

## Wiring

| File | Binds |
|------|-------|
| `bootstrap/providers.php` | `AppointmentServiceProvider`, `PrescriptionServiceProvider` |
| `app/Providers/AppointmentServiceProvider.php` | Scheduling + Expiration + Clock ports → InMemory |
| `app/Providers/AuditServiceProvider.php` | `AuditLogPort` → InMemory |
| `app/Providers/CatalogServiceProvider.php` | doctor/patient command/query ports → InMemory |
| `app/Providers/PrescriptionServiceProvider.php` | `PrescriptionCommandPort` / `PrescriptionQueryPort` → InMemory |
| `routes/api.php` | All HTTP routes |
| `bootstrap/app.php` | Registers `routes/api.php` |

## Hexagon code

`app/CONTEXT.md` — mirror of `pure-php/src/` (no Redis adapters here).

## Ignore for domain work

`app/Models/User.php`, `database/`, default `Http/Controllers/Controller.php` — scaffold only.

## Sync rule

Logic changes: edit `pure-php/src/` first, then copy/sync `app/Domain`, `app/Application`, `app/Infrastructure/Persistence/InMemory`.
