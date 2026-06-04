<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\Redis;

use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Application\Port\SchedulingQueryPort;
use HexagonPractise\Domain\Scheduling\AppointmentHold;
use HexagonPractise\Domain\Scheduling\AppointmentNotFoundException;
use HexagonPractise\Domain\Scheduling\NoSlotsAvailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\PatientId;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;
use Predis\Client;

final class RedisSchedulingAdapter implements SchedulingCommandPort, SchedulingQueryPort
{
    private readonly string $holdScript;
    private readonly string $releaseScript;
    private readonly string $confirmScript;

    public function __construct(
        private readonly Client $redis,
        private readonly string $slotsKeyPrefix,
        private readonly string $appointmentKeyPrefix,
    ) {
        $this->holdScript = LuaScriptLoader::load('hold_appointment.lua');
        $this->releaseScript = LuaScriptLoader::load('release_appointment.lua');
        $this->confirmScript = LuaScriptLoader::load('confirm_appointment.lua');
    }

    public function setAvailability(PractitionerId $practitionerId, SlotCount $slots): void
    {
        $this->redis->set($this->slotsKey($practitionerId), (string) $slots->value);
    }

    public function availableSlots(PractitionerId $practitionerId): SlotCount
    {
        $value = $this->redis->get($this->slotsKey($practitionerId));

        return new SlotCount((int) ($value ?? 0));
    }

    public function hold(AppointmentHold $hold): AppointmentHold
    {
        $result = $this->redis->eval(
            $this->holdScript,
            2,
            $this->slotsKey($hold->practitionerId),
            $this->appointmentKey($hold->id),
            (string) $hold->slots->value,
            (string) $hold->practitionerId->value,
            $hold->patientId->value,
            $hold->expiresAt->format(\DateTimeInterface::ATOM),
        );

        if (!is_array($result) || (int) ($result[0] ?? 0) !== 1) {
            $available = new SlotCount((int) ($result[1] ?? 0));
            throw new NoSlotsAvailableException(
                practitionerId: $hold->practitionerId,
                requested     : $hold->slots,
                available     : $available,
            );
        }

        return $hold;
    }

    public function cancelHold(AppointmentId $appointmentId): void
    {
        $hold = $this->findHold($appointmentId);
        if ($hold === null) {
            throw new AppointmentNotFoundException($appointmentId);
        }

        $ok = $this->redis->eval(
            $this->releaseScript,
            2,
            $this->slotsKey($hold->practitionerId),
            $this->appointmentKey($appointmentId),
        );

        if ((int) $ok !== 1) {
            throw new AppointmentNotFoundException($appointmentId);
        }
    }

    public function confirm(AppointmentId $appointmentId): void
    {
        $ok = $this->redis->eval(
            $this->confirmScript,
            1,
            $this->appointmentKey($appointmentId),
        );

        if ((int) $ok !== 1) {
            throw new AppointmentNotFoundException($appointmentId);
        }
    }

    public function findHold(AppointmentId $appointmentId): ?AppointmentHold
    {
        $data = $this->redis->hgetall($this->appointmentKey($appointmentId));
        if ($data === [] || !isset($data['practitioner_id'], $data['patient_id'], $data['slots'], $data['expires_at'])) {
            return null;
        }

        return new AppointmentHold(
            id            : $appointmentId,
            practitionerId: new PractitionerId((int) $data['practitioner_id']),
            patientId     : new PatientId($data['patient_id']),
            slots         : new SlotCount((int) $data['slots']),
            expiresAt     : new \DateTimeImmutable($data['expires_at']),
        );
    }

    private function slotsKey(PractitionerId $practitionerId): string
    {
        return $this->slotsKeyPrefix . $practitionerId->value;
    }

    private function appointmentKey(AppointmentId $id): string
    {
        return $this->appointmentKeyPrefix . $id->value;
    }
}
