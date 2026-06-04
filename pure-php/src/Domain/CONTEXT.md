# Domain

No imports from Application/Infrastructure/framework.

## Audit/

| Class | Role |
|-------|------|
| `AuditLogEntry` | Compliance fields: `timestamp` (UTC ms), `actor_id`, `actor_role`, `patient_id`, `action_type`, `ip_address`, `device_id`, `before_state`/`after_state`, `state_diff`, outcome, exception |
| `AuditOutcome` | `success` / `failure` |
| `AuditSensitiveDataSanitizer` | Redacts passwords, SSN, cards, `instructions`, `pharmacy_notes`, psychotherapy fields |
| `AuditStateDiff` | Human-readable `field: "old" -> "new"` |

**Security:** Audit store is append-only (`AuditLogPort` has no update/delete), separate InMemory adapter — not the primary Redis/DB. Never persist raw clinical narrative or credentials; sanitizer enforces `[REDACTED]`.

## Doctor/

| Class | Role |
|-------|------|
| `Doctor` | Registered clinician (`PractitionerId` + name) |
| `DoctorNotFoundException` | Unknown doctor when scheduling |

## Patient/

| Class | Role |
|-------|------|
| `Patient` | Registered person (`PatientId` + name) |
| `PatientNotFoundException` | Unknown patient when booking |

## Shared (value objects)

| Class | Meaning |
|-------|---------|
| `PractitionerId` | Clinician / schedule resource (same id as `Doctor`; positive int) |
| `PatientId` | Person receiving care |
| `AppointmentId` | Appointment hold id |
| `PrescriptionId` | Prescription id |
| `SlotCount` | Non-negative slot count |
| `ActorRole` | `doctor` \| `pharmacist` |

## Scheduling/

| Class | Role |
|-------|------|
| `AppointmentHold` | Temporary slot reservation + `expiresAt` |
| `NoSlotsAvailableException` | Hold exceeds available slots |
| `AppointmentNotFoundException` | Unknown appointment id |

## Prescription/

| Class | Role |
|-------|------|
| `Prescription` | Aggregate; field `version` for optimistic locking |
| `PrescriptionStatus` | `draft` \| `active` \| `dispensed` |
| `ConcurrentUpdateException` | Stale `expected_version` (doctor/pharmacist race) |
| `PrescriptionNotFoundException` | Unknown prescription |
| `UnauthorizedPrescriptionChangeException` | Role touched forbidden field |

## Expiration/

| Class | Role |
|-------|------|
| `ExpiringItem` | Id + JSON `payload` + `expiresAt` for ZSET queue |
