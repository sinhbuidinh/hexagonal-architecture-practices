# Infrastructure

Implements ports. May use Domain + Application.

## HTTP (`Infrastructure/Http/`)

| Controller | Routes (no `/api`) |
|------------|-------------------|
| `DoctorController` | POST `/doctors` |
| `PatientController` | POST `/patients` |
| `AppointmentController` | GET `/appointments/bookable/{doctorId}`, POST `/availability` (`time_slots`), `/appointments` (`bookable_slot_id`), `.../cancel`, `.../confirm`, `/expiration/process` |
| `PrescriptionController` | POST `/prescriptions`, GET/PUT `/prescriptions/{id}` |
| `AuditLogController` | GET `/audit-logs?limit=` |

PUT prescription: body `actor`, `expected_version`, fields. **409** = `ConcurrentUpdateException`.

## Persistence

### InMemory (`Persistence/InMemory/`)

Default for tests & `--in-memory`. No true parallelism; still enforces version checks.

### Redis (`Persistence/Redis/`)

| Adapter | Lua scripts |
|---------|-------------|
| `RedisSchedulingAdapter` | `hold_appointment`, `release_appointment`, `confirm_appointment` |
| `RedisExpirationQueueAdapter` | `poll_expiration` |
| `RedisPrescriptionAdapter` | `update_prescription` (CAS on version) |

Keys: `config/app.php` prefixes (`scheduling:slots:`, `scheduling:appointment:`, `prescription:`).

## Clock

`SystemClock` (prod), `FrozenClock` (tests).

## Event-driven exceptions

Use cases still **throw** domain exceptions. HTTP does not map them inline:

1. `DomainExceptionHandler` dispatches `DomainExceptionOccurred`
2. `ExceptionResponseListener` implementations attach `HttpErrorResponse` (first match wins)
3. `HttpActionRunner` wraps controller actions (pure-php array payload; Laravel/Symfony `JsonResponse`)

Listeners live in `Infrastructure/Event/Listener/`. Symfony also subscribes via `DomainExceptionSubscriber` on `kernel.exception`; Laravel via `bootstrap/app.php` `render`.

**Audit HTTP headers (optional):** `X-Actor-Id`, `X-Actor-Role`, `X-Device-Id` — or body fields `actor`, `actor_id`, `patient_id`. Body `instructions` / `pharmacy_notes` are never written to audit logs ([REDACTED]).

## Bootstrap

`Container::fromConfig($config, $useInMemory)` — single place for adapter selection.
