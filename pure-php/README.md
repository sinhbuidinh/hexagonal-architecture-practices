# pure-php — Healthcare scheduling (hexagonal)

Plain PHP reference for **practitioner appointment scheduling** with **expiration of unpaid holds**, using **Hexagonal Architecture**.

## Architecture

```
src/
├── Domain/
│   ├── Scheduling/     # AppointmentHold, scheduling exceptions
│   ├── Expiration/     # ExpiringItem
│   └── Shared/         # PractitionerId, PatientId, AppointmentId, SlotCount
├── Application/
│   ├── Port/           # *CommandPort, *QueryPort, ExpirationQueuePort, ClockPort
│   ├── Scheduling/     # Use cases
│   └── Expiration/
└── Infrastructure/     # Redis Lua, InMemory, HTTP, CLI, Clock
```

## CLI

```bash
php bin/console --in-memory availability:set dr-smith 20
php bin/console --in-memory appointment:hold apt-1 dr-smith patient-42 1 "+15 minutes"
php bin/console --in-memory appointment:cancel apt-1
php bin/console --in-memory appointment:confirm apt-1
php bin/console --in-memory expiration:process
```

## HTTP

```bash
USE_IN_MEMORY=1 composer serve
curl -X POST http://localhost:8080/availability -d '{"practitioner_id":"dr-smith","slots":20}'
curl -X POST http://localhost:8080/appointments -d '{"appointment_id":"apt-1","practitioner_id":"dr-smith","patient_id":"patient-42","slots":1,"expires_at":"2026-06-03T12:00:00+00:00"}'
curl -X POST http://localhost:8080/expiration/process
```

## Prescriptions (optimistic locking)

Prevents lost updates when a **doctor** and **pharmacist** edit the same prescription. Every PUT must include `expected_version` from the last GET.

```bash
php bin/console --in-memory prescription:create rx-1 patient-1 Amoxicillin 500mg
php bin/console --in-memory prescription:update rx-1 \
  '{"actor":"doctor","expected_version":1,"status":"active"}'
```

Full race-condition walkthrough: [../docs/examples/prescription-race-condition.md](../docs/examples/prescription-race-condition.md).

## Redis keys

| Key pattern                         | Purpose                    |
|-------------------------------------|----------------------------|
| `scheduling:slots:{practitionerId}` | Available slot count       |
| `scheduling:appointment:{id}`       | Active appointment hold    |
| `expiration:queue`                  | ZSET of due expirations    |
| `prescription:{id}`                 | Prescription hash + version |

## License

MIT
