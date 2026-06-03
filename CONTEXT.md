# hexagon-practise — project map (read first)

Monorepo: **same hexagonal domain/application**, three runtimes.

| Path | Role |
|------|------|
| `pure-php/` | Reference impl: Redis Lua, CLI, PHPUnit (`HexagonPractise\`) |
| `laravel/` | Laravel 13 DI + `routes/api.php` (`App\`) |
| `symfony/` | Symfony 8.1 DI + attribute routes (`App\`) |
| `docs/examples/` | Deep-dive walkthroughs |

## Hexagon rule

```
Domain ← Application ← Infrastructure
```

- **Domain**: entities, VOs, domain exceptions (no framework/Redis).
- **Application**: use cases + **Port** interfaces.
- **Infrastructure**: adapters (HTTP, Redis, InMemory), wiring.

Inbound: HTTP controllers / CLI → use cases.  
Outbound: adapters implement ports.

## Bounded contexts

| Context | Problem | Concurrency |
|---------|---------|-------------|
| **Scheduling** | Practitioner slots + appointment holds + TTL expiry | Redis Lua atomic hold/release |
| **Prescription** | Doctor/pharmacist edit same Rx | Optimistic lock (`version` + `expected_version`) |

## CONTEXT.md index

| File | Contents |
|------|----------|
| `pure-php/CONTEXT.md` | Entry, run, config |
| `pure-php/src/CONTEXT.md` | Layer layout |
| `pure-php/src/Domain/CONTEXT.md` | Entities & VOs |
| `pure-php/src/Application/CONTEXT.md` | Ports & use cases |
| `pure-php/src/Infrastructure/CONTEXT.md` | Adapters & routes |
| `pure-php/tests/CONTEXT.md` | Test map |
| `laravel/CONTEXT.md` | Laravel wiring |
| `laravel/app/CONTEXT.md` | `app/` hexagon mirror |
| `symfony/CONTEXT.md` | Symfony wiring |
| `symfony/src/CONTEXT.md` | `src/` hexagon mirror |
| `docs/CONTEXT.md` | Example docs |

**Canonical logic** lives in `pure-php/src/`. Laravel/Symfony copy `Domain` + `Application` + InMemory adapters; change wiring only unless noted.

## Code style

Root: `composer format` / `format:check` — full tree. **`format:changed`** / **`phpstan:changed`** — git-changed PHP only (`SCOPE=worktree|staged|branch`, default `worktree`). PHP-CS-Fixer **PSR-12** (`.php-cs-fixer.dist.php`): aligned `=>`, `=` (by scope), multiline named-arg `:`, **4 spaces**. `composer spaces:check` — no tabs. `composer phpstan` — multiline calls ⇒ named args (`tools/phpstan/`; baseline `phpstan-baseline-*.neon`). Same from `pure-php/`, `laravel/`, or `symfony/`.

## Cursor setup

`CONTEXT.md` files are **not** auto-loaded by themselves. This repo includes `.cursor/rules/hexagon-context.mdc` (`alwaysApply: true`): read CONTEXT first; if something is missing, use **grep/search** on the project (scoped), then read only relevant files. You can also `@CONTEXT.md` in chat.
