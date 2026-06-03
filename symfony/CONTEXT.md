# symfony

Same hexagon as `pure-php/`, namespace `App\`, Symfony = **DI YAML + attribute routes**.

## Run

```bash
composer install
symfony server:start   # or php -S localhost:8000 -t public
# Routes: /api/...
```

## Wiring

| File | Role |
|------|------|
| `config/services.yaml` | Autowire `App\` (excludes `Domain/`) |
| `config/services_appointment.yaml` | Port → InMemory scheduling + expiration + clock |
| `config/services_catalog.yaml` | doctor/patient command/query ports → InMemory |
| `config/services_prescription.yaml` | prescription command/query ports → InMemory |
| `src/Kernel.php` | App kernel |

## Hexagon code

`src/CONTEXT.md`.

## Sync rule

Same as Laravel: canonical changes in `pure-php/src/`, then sync `symfony/src/`.
