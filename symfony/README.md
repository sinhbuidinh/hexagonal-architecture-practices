# Symfony — Healthcare appointment scheduling

Hexagonal layers under `src/`, with port bindings in `config/services_appointment.yaml`.

## Run

```bash
composer install
symfony server:start
```

```bash
curl -X POST http://127.0.0.1:8000/api/availability \
  -H 'Content-Type: application/json' \
  -d '{"practitioner_id":"dr-smith","slots":20}'
```

See root `README.md` for the full API.
