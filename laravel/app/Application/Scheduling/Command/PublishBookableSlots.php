<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\BookableSlotCommandPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Shared\PractitionerId;

final readonly class PublishBookableSlots
{
    public function __construct(
        private BookableSlotCommandPort $bookableSlots,
        private DoctorQueryPort $doctors,
    ) {
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $timeSlots
     *
     * @return list<array{slot_id: int, date: string, start_time: string, end_time: string}>
     */
    public function execute(int $practitionerId, array $timeSlots): array
    {
        $id = new PractitionerId($practitionerId);
        if ($this->doctors->find($id) === null) {
            throw new DoctorNotFoundException($id);
        }

        $published = [];
        foreach ($timeSlots as $window) {
            $slot = $this->bookableSlots->publish(
                $id,
                (string) $window['date'],
                (string) $window['start_time'],
                (string) $window['end_time'],
            );

            $published[] = [
                'slot_id'    => $slot->id->value,
                'date'       => $slot->date,
                'start_time' => $slot->startTime,
                'end_time'   => $slot->endTime,
            ];
        }

        return $published;
    }
}
