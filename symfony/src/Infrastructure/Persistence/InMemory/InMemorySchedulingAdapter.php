<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\SchedulingCommandPort;
use App\Application\Port\SchedulingQueryPort;
use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Scheduling\AppointmentNotFoundException;
use App\Domain\Scheduling\NoSlotsAvailableException;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

final class InMemorySchedulingAdapter implements SchedulingCommandPort, SchedulingQueryPort
{
    /** @var array<int, int> */
    private array $availability = [];

    /** @var array<string, AppointmentHold> */
    private array $holds = [];

    private int $nextAppointmentId = 1;

    public function setAvailability(PractitionerId $practitionerId, SlotCount $slots): void
    {
        $this->availability[$practitionerId->value] = $slots->value;
    }

    public function availableSlots(PractitionerId $practitionerId): SlotCount
    {
        return new SlotCount($this->availability[$practitionerId->value] ?? 0);
    }

    public function hold(AppointmentHold $hold): AppointmentHold
    {
        if ($hold->bookableSlotId === null) {
            $available = $this->availableSlots($hold->practitionerId);
            if (!$available->isGreaterOrEqual($hold->slots)) {
                throw new NoSlotsAvailableException(
                    practitionerId: $hold->practitionerId,
                    requested     : $hold->slots,
                    available     : $available,
                );
            }

            $key = $hold->practitionerId->value;
            $this->availability[$key] = $available->subtract($hold->slots)->value;
        }

        $stored = new AppointmentHold(
            id            : new AppointmentId((string) $this->nextAppointmentId++),
            practitionerId: $hold->practitionerId,
            patientId     : $hold->patientId,
            slots         : $hold->slots,
            expiresAt     : $hold->expiresAt,
            bookableSlotId: $hold->bookableSlotId,
        );

        $this->holds[$stored->id->value] = $stored;

        return $stored;
    }

    public function cancelHold(AppointmentId $appointmentId): void
    {
        $hold = $this->holds[$appointmentId->value] ?? null;
        if ($hold === null) {
            throw new AppointmentNotFoundException($appointmentId);
        }

        if ($hold->bookableSlotId === null) {
            $key = $hold->practitionerId->value;
            $this->availability[$key] = ($this->availability[$key] ?? 0) + $hold->slots->value;
        }

        unset($this->holds[$appointmentId->value]);
    }

    public function confirm(AppointmentId $appointmentId): void
    {
        if (!isset($this->holds[$appointmentId->value])) {
            throw new AppointmentNotFoundException($appointmentId);
        }

        unset($this->holds[$appointmentId->value]);
    }

    public function findHold(AppointmentId $appointmentId): ?AppointmentHold
    {
        return $this->holds[$appointmentId->value] ?? null;
    }
}
