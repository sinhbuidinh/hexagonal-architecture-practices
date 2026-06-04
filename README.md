# hexagon-practise

> **Cursor / AI:** Start with [`CONTEXT.md`](CONTEXT.md) — short per-folder summaries to map the repo without reading all source.

Monorepo comparing **Hexagonal Architecture** for **healthcare appointment scheduling** across three PHP stacks.

| Folder      | Stack        | Notes                                              |
|-------------|--------------|----------------------------------------------------|
| `pure-php/` | Plain PHP    | Full reference: Redis Lua, HTTP, CLI, PHPUnit      |
| `laravel/`  | Laravel 13   | Same domain/application; Laravel DI + API routes   |
| `symfony/`  | Symfony 8.1  | Same domain/application; Symfony DI + attributes   |

Practitioners have bookable **slots**. Patients get **appointment holds** (temporary) that can be **confirmed** or **cancelled**, with automatic expiry via a queue.

## Quick start

### Pure PHP

```bash
cd pure-php
composer install
composer test
php bin/console --in-memory availability:set dr-smith 20
php bin/console --in-memory appointment:hold apt-1 dr-smith patient-42 1 "+15 minutes"
```

### Laravel

```bash
cd laravel && php artisan serve
curl -X POST http://127.0.0.1:8000/api/availability \
  -H 'Content-Type: application/json' \
  -d '{"practitioner_id":"dr-smith","slots":20}'
```

### Symfony

```bash
cd symfony && symfony server:start
curl -X POST http://127.0.0.1:8000/api/appointments \
  -H 'Content-Type: application/json' \
  -d '{"appointment_id":"apt-1","practitioner_id":"dr-smith","patient_id":"patient-42","slots":1}'
```

## Shared API (Laravel & Symfony)

| Method | Path                                    | Action                    |
|--------|-----------------------------------------|---------------------------|
| POST   | `/api/availability`                     | Set practitioner slots    |
| POST   | `/api/appointments`                     | Hold appointment          |
| POST   | `/api/appointments/{id}/cancel`         | Cancel hold               |
| POST   | `/api/appointments/{id}/confirm`        | Confirm appointment       |
| POST   | `/api/expiration/process`               | Process expired holds     |

Pure PHP uses the same paths without the `/api` prefix.

## Domain model

| Concept        | Role                                              |
|----------------|---------------------------------------------------|
| `PractitionerId` | Clinician or resource with a slot pool            |
| `PatientId`      | Person booking the visit                          |
| `AppointmentId`  | Unique appointment / hold identifier              |
| `SlotCount`      | Number of concurrent bookable slots               |
| `AppointmentHold`| Temporary booking until confirm or expiry         |

## Use cases

| Use case                    | Description                                      |
|-----------------------------|--------------------------------------------------|
| `SetPractitionerAvailability` | Open N slots for a practitioner                |
| `HoldAppointment`           | Reserve slots + schedule expiry                  |
| `CancelAppointmentHold`     | Release slots and remove hold                      |
| `ConfirmAppointment`        | Finalize visit (slots stay taken)                |
| `ProcessExpiredItems`       | Auto-cancel holds past `expires_at`              |

### Prescription race conditions (doctor vs pharmacist)

Concurrent updates use **optimistic locking** (`expected_version` on every PUT). If two actors read version `1` and both try to save, the second receives **409 Conflict** and must reload.

See [docs/examples/prescription-race-condition.md](docs/examples/prescription-race-condition.md) for a walkthrough and curl/CLI examples.

| Method | Path (Laravel/Symfony: `/api/...`) | Action |
|--------|--------------------------------------|--------|
| POST   | `/prescriptions`                     | Create (version 1) |
| GET    | `/prescriptions/{id}`                | Read current version |
| PUT    | `/prescriptions/{id}`                | Update with `actor` + `expected_version` |

## Code style

The monorepo uses [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) with the **PSR-12** ruleset (the current successor to PSR-2), plus `declare(strict_types=1)` and short array syntax.

**Indentation:** 4 spaces, no tabs (`.editorconfig`). PHP-CS-Fixer rule `indentation_type` enforces spaces in PHP; `composer spaces:check` fails if tabs appear in project sources.

```bash
# From repo root (install once) — all packages
composer install
composer format          # fix all app PHP under pure-php/, laravel/, symfony/
composer format:check    # CI-style dry run
composer spaces:check    # fail if any tab in project sources (excludes vendor/)
composer spaces:fix      # expand tabs → 4 spaces (also runs after phpstan:baseline)
composer phpstan         # all three stacks

# From a package folder — that package only (delegates to root scoped scripts)
cd laravel && composer format:check
cd pure-php && composer phpstan
```

### Changed files only (fast local / PR checks)

By default **`SCOPE=worktree`** — unstaged + staged edits vs `HEAD` (what you are working on right now).

| Command | What it runs on |
|---------|-----------------|
| `composer format:changed` (root) | Changed PHP in any package |
| `composer format:changed:check` (root) | Dry-run, all packages |
| `composer phpstan:changed` (root) | Changed PHP (picks config per file) |
| `cd laravel && composer format:changed` | Changed PHP under `laravel/` only |

```bash
# Default: files you edited but have not committed yet (root = all packages)
composer format:changed
composer phpstan:changed

# Package folder = only that tree
cd symfony && composer format:changed:check

# Only staged files (pre-commit)
SCOPE=staged composer format:changed
SCOPE=staged composer phpstan:changed

# Whole branch vs main (CI / before PR)
SCOPE=branch composer format:changed:check
SCOPE=branch composer phpstan:changed

# Custom merge base
SCOPE=branch BASE=origin/main composer phpstan:changed
```

Scripts: [`tools/format-changed.sh`](tools/format-changed.sh), [`tools/phpstan-changed.sh`](tools/phpstan-changed.sh). If nothing matches, they exit 0 with a short message.

Config: [`.php-cs-fixer.dist.php`](.php-cs-fixer.dist.php) (PSR-12; aligned `=>`, consecutive-line `=`, multiline named-arg `:`).

### Static analysis (named arguments on multiline calls)

Custom PHPStan rule **`hexagon.multilineCallRequiresNamedArguments`**: if a function/method/`new` call spans multiple lines, every argument must be named.

```bash
composer phpstan              # all packages (root only)
composer phpstan:laravel      # single stack (also via cd laravel && composer phpstan)
composer phpstan:baseline     # refresh baselines after fixing violations
```

Rule: [`tools/phpstan/Rules/MultilineCallRequiresNamedArgumentsRule.php`](tools/phpstan/Rules/MultilineCallRequiresNamedArgumentsRule.php). Baselines: `phpstan-baseline-*.neon`. Laravel also ships [Pint](https://laravel.com/docs/pint) (`laravel/vendor/bin/pint`); prefer the root formatter so all three stacks stay aligned.

## License

MIT
