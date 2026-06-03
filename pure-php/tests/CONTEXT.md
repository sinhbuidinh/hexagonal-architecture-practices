# tests

PHPUnit; bootstrap `vendor/autoload.php`.

## Unit

| File | Covers |
|------|--------|
| `Unit/Domain/SlotCountTest.php` | VO math |
| `Unit/Application/HoldAndExpireTest.php` | Scheduling + expiry (InMemory) |
| `Unit/Application/PrescriptionRaceTest.php` | Doctor wins version race; pharmacist retries |

## Integration

| File | Covers |
|------|--------|
| `Integration/RedisSchedulingAdapterTest.php` | Lua hold/cancel; **skipped** if Redis down |

Run: `composer test` from `pure-php/`.
