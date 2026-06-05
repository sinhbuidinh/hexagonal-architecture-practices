# pure-php

**Canonical** hexagon reference. Namespace: `HexagonPractise\`.

## Run

```bash
composer install && composer test
php bin/console <command>               # hybrid: MySQL catalog + Redis scheduling
php bin/console --in-memory <command>   # all in-memory (no MySQL/Redis)
USE_IN_MEMORY=1 composer serve          # HTTP :8080 in-memory; else hybrid
```

Hybrid needs MySQL (Laravel hexagon migration) + Redis. Env: `DATABASE_URL`, `REDIS_DSN`.

## Entry points

| File | Role |
|------|------|
| `public/index.php` | HTTP: `AppointmentController` → 404 → `PrescriptionController` |
| `bin/console` | CLI commands |
| `src/Bootstrap/Container.php` | Manual DI: picks InMemory vs Redis adapters |
| `config/app.php` | Redis DSN, key prefixes, `CLINIC_LUNCH_BREAK_*` |

## Layout

See `src/CONTEXT.md`. Subdocs: `src/Domain/`, `src/Application/`, `src/Infrastructure/`.

## Tests

`tests/CONTEXT.md` — unit (InMemory), integration (Redis skip if down).

## Redis-only extras

Lua under `src/Infrastructure/Persistence/Redis/Lua/`. Not copied to Laravel/Symfony yet.
