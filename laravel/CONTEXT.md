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
| `config/hexagon.php` | `BOOKABLE_SLOT_HORIZON_DAYS`, `CLINIC_LUNCH_BREAK_*` (global lunch gap for slot generation) |
| `app/Providers/AppointmentServiceProvider.php` | Scheduling + bookable slots + appointment settings → MySql |
| `routes/console.php` | `hexagon:materialize-bookable-slots` daily 00:01 |
| `app/Providers/AuditServiceProvider.php` | `AuditLogPort` → MySql |
| `app/Providers/CatalogServiceProvider.php` | doctor/patient command/query ports → MySql |
| `app/Providers/PrescriptionServiceProvider.php` | `PrescriptionCommandPort` / `PrescriptionQueryPort` → MySql |
| `app/Providers/AuthServiceProvider.php` | JWT auth (`lcobucci/jwt`, HS256) |
| `config/jwt.php` | `JWT_SECRET` / `JWT_ISSUER` / `JWT_TTL` |
| `routes/api.php` | All HTTP routes (require `Authorization: Bearer {jwt}`) |
| `bootstrap/app.php` | Registers `routes/api.php` |

## Hexagon code

`app/CONTEXT.md` — mirror of `pure-php/src/` (no Redis adapters here).

## Ignore for domain work

`app/Models/User.php`, `database/`, default `Http/Controllers/Controller.php` — scaffold only.

## Sync rule

Logic changes: edit `pure-php/src/` first, then copy/sync `app/Domain`, `app/Application`, `app/Infrastructure/Persistence/InMemory`.
