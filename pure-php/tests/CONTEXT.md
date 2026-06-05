# tests

PHPUnit; bootstrap `vendor/autoload.php`.

## Unit

| File | Covers |
|------|--------|
| `Unit/Domain/SlotCountTest.php` | VO math |
| `Unit/Application/HoldAndExpireTest.php` | Scheduling + expiry (InMemory) |
| `Unit/Application/PrescriptionRaceTest.php` | Doctor wins version race; pharmacist retries |
| `Unit/Infrastructure/ClinicLunchBreakFromConfigTest.php` | `CLINIC_LUNCH_BREAK_*` config parsing |
| `Unit/Application/MaterializeBookableSlotsLunchBreakTest.php` | Materialized slots respect configured lunch gap |
| `Unit/Domain/BookableSlotGeneratorTest.php` | Slot generation + lunch carve-out |

## Integration

| File | Covers |
|------|--------|
| `Integration/RedisSchedulingAdapterTest.php` | Lua hold/cancel; **skipped** if Redis down |
| `Integration/MySqlCatalogAdapterTest.php` | Doctor/patient MySQL round-trip; **skipped** if MySQL down |

Run: `composer test` from `pure-php/`.
