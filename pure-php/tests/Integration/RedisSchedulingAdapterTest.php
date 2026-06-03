<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Integration;

use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisClientFactory;
use HexagonPractise\Infrastructure\Persistence\Redis\RedisSchedulingAdapter;
use PHPUnit\Framework\TestCase;
use Predis\Client;

final class RedisSchedulingAdapterTest extends TestCase
{
    private ?Client $redis                   = null;
    private ?RedisSchedulingAdapter $adapter = null;

    protected function setUp(): void
    {
        $dsn           = getenv('REDIS_DSN') ?: 'redis://127.0.0.1:6379';
        try {
            $this->redis = RedisClientFactory::fromDsn($dsn);
            $this->redis->ping();
        } catch (\Throwable) {
            $this->markTestSkipped('Redis is not available: ' . $dsn);
        }

        $this->adapter = new RedisSchedulingAdapter(
            $this->redis,
            'test:slots:',
            'test:appointment:',
        );

        $keys          = $this->redis->keys('test:*');
        if ($keys !== []) {
            $this->redis->del($keys);
        }
    }

    public function testAtomicHoldAndCancel(): void
    {
        $practitioner = new PractitionerId('dr-' . bin2hex(random_bytes(4)));
        $this->adapter->setAvailability($practitioner, new SlotCount(5));

        $hold         = new AppointmentHold(
            new AppointmentId('apt-' . bin2hex(random_bytes(4))),
            $practitioner,
            new PatientId('patient-1'),
            new SlotCount(3),
            new \DateTimeImmutable('+10 minutes'),
        );

        $this->adapter->hold($hold);
        $this->assertSame(2, $this->adapter->availableSlots($practitioner)->value);

        $this->adapter->cancelHold($hold->id);
        $this->assertSame(5, $this->adapter->availableSlots($practitioner)->value);
    }
}
