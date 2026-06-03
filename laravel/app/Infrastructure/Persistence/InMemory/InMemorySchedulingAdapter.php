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
    /** @var array<string, int> */
    private array $availability = [];

    /** @var array<string, AppointmentHold> */
    private array $holds = [];

    public function setAvailability(PractitionerId $practitionerId, SlotCount $slots): void
    {
        $this->availability[$practitionerId->value] = $slots->value;
    }

    public function availableSlots(PractitionerId $practitionerId): SlotCount
    {
        return new SlotCount($this->availability[$practitionerId->value] ?? 0);
    }

    public function hold(AppointmentHold $hold): void
    {
        $available = $this->availableSlots($hold->practitionerId);
        if (!$available->isGreaterOrEqual($hold->slots)) {
            throw new NoSlotsAvailableException(
                $hold->practitionerId,
                $hold->slots,
                $available,
            );
        }

        $key = $hold->practitionerId->value;
        $this->availability[$key] = $available->subtract($hold->slots)->value;
        $this->holds[$hold->id->value] = $hold;
    }

    public function cancelHold(AppointmentId $appointmentId): void
    {
        $hold = $this->holds[$appointmentId->value] ?? null;
        if ($hold === null) {
            throw new AppointmentNotFoundException($appointmentId);
        }

        $key = $hold->practitionerId->value;
        $this->availability[$key] = ($this->availability[$key] ?? 0) + $hold->slots->value;
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
