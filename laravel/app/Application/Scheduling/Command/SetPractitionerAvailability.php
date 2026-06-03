<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\DoctorQueryPort;
use App\Application\Port\SchedulingCommandPort;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;

final readonly class SetPractitionerAvailability
{
    public function __construct(
        private SchedulingCommandPort $scheduling,
        private DoctorQueryPort $doctors,
    ) {
    }

    public function execute(string $practitionerId, int $slots): void
    {
        $id = new PractitionerId($practitionerId);
        if ($this->doctors->find($id) === null) {
            throw new DoctorNotFoundException($id);
        }

        $this->scheduling->setAvailability($id, new SlotCount($slots));
    }
}
