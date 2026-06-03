# pure-php

**Canonical** hexagon reference. Namespace: `HexagonPractise\`.

## Run

```bash
composer install && composer test
php bin/console --in-memory <command>   # no Redis
USE_IN_MEMORY=1 composer serve          # HTTP :8080, no /api prefix
```

## Entry points

| File | Role |
|------|------|
| `public/index.php` | HTTP: `AppointmentController` → 404 → `PrescriptionController` |
| `bin/console` | CLI commands |
| `src/Bootstrap/Container.php` | Manual DI: picks InMemory vs Redis adapters |
| `config/app.php` | Redis DSN + key prefixes |

## Layout

See `src/CONTEXT.md`. Subdocs: `src/Domain/`, `src/Application/`, `src/Infrastructure/`.

## Tests

`tests/CONTEXT.md` — unit (InMemory), integration (Redis skip if down).

## Redis-only extras

Lua under `src/Infrastructure/Persistence/Redis/Lua/`. Not copied to Laravel/Symfony yet.
