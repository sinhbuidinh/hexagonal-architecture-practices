# Laravel — Healthcare appointment scheduling

Hexagonal **Domain** and **Application** layers wired with Laravel DI and API routes.

## Run

```bash
composer install
php artisan serve
```

```bash
curl -X POST http://127.0.0.1:8000/api/availability \
  -H 'Content-Type: application/json' \
  -d '{"practitioner_id":"dr-smith","slots":20}'

curl -X POST http://127.0.0.1:8000/api/appointments \
  -H 'Content-Type: application/json' \
  -d '{"appointment_id":"apt-1","practitioner_id":"dr-smith","patient_id":"patient-42","slots":1}'
```

Default adapters are in-memory. Copy Redis scheduling adapters from `pure-php/` to add production persistence.
