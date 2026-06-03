<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Application\Port\SchedulingCommandPort;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

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
