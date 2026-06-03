# Application

Use cases depend on **Domain** + **Port** interfaces only. **CQRS**: writes use `*CommandPort` + `*/Command/*`; reads use `*QueryPort` + `*/Query/*`.

**Exceptions → HTTP** (event-driven, not in use cases): `Application\Event\DomainExceptionOccurred` + `Application\Port\ExceptionResponseListener` (implemented in Infrastructure).

**Audit log** (event-driven): `ActionAudited` carries full `AuditMetadata` (actor, patient, action_type, IP, device, before/after diff). Built by `AuditRecordBuilder` + `AuditRequestContext` (HTTP headers `X-Actor-Id`, `X-Actor-Role`, `X-Device-Id`). Sanitized before persist.

## Ports (`Application/Port/`)

| Port | Adapter(s) | Purpose |
|------|------------|---------|
| `SchedulingCommandPort` | InMemory, Redis+Lua | set availability, hold/cancel/confirm |
| `SchedulingQueryPort` | same adapter | available slots, find hold |
| `ExpirationQueuePort` | InMemory, Redis ZSET | Schedule/poll expiring items |
| `PrescriptionCommandPort` | InMemory, Redis+Lua | save, `updateIfVersionMatches` |
| `PrescriptionQueryPort` | same adapter | find by id |
| `DoctorCommandPort` / `DoctorQueryPort` | InMemory | register + list doctors |
| `PatientCommandPort` / `PatientQueryPort` | InMemory | register patients |
| `ClockPort` | System, Frozen | Injectable time (tests) |
| `AuditLogPort` | InMemory | append + listRecent |

## Doctor / Patient / Booking

| Class | Layer |
|-------|-------|
| `Doctor/Command/CreateDoctor` | `DoctorCommandPort` |
| `Patient/Command/CreatePatient` | `PatientCommandPort` |
| `Booking/Query/ListBookableAppointments` | `DoctorQueryPort` + `SchedulingQueryPort` (slots > 0) |

`SetPractitionerAvailability` and `HoldAppointment` require registered doctor/patient.

## Scheduling — commands (`Scheduling/Command/`)

| Class | Calls |
|-------|-------|
| `SetPractitionerAvailability` | `setAvailability` |
| `HoldAppointment` | `hold` + enqueue `appointment:{id}` expiry |
| `CancelAppointmentHold` | `cancelHold` + cancel queue item |
| `ConfirmAppointment` | `confirm` + cancel queue item |

Reads: `SchedulingQueryPort` (`availableSlots`, `findHold`) — used by adapters today; add `Scheduling/Query/*` handlers when exposing GET APIs.

## Prescription — commands (`Prescription/Command/`)

| Class | Calls |
|-------|-------|
| `CreatePrescription` | `save` (version=1) |
| `UpdatePrescription` | load via query port → `updateIfVersionMatches` |

## Prescription — queries (`Prescription/Query/`)

| Class | Calls |
|-------|-------|
| `GetPrescription` | `find` |

Role fields on update: doctor → medication/dosage/instructions/status; pharmacist → pharmacy_notes/status.

## Expiration

| Class | Calls |
|-------|-------|
| `ProcessExpiredItems` | `pollDue` → for `appointment_hold` payload → `CancelAppointmentHold` |

Payload type: `appointment_hold` (not prescription).
