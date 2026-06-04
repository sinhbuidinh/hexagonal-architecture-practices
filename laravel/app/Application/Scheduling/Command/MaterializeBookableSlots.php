<?php

declare(strict_types=1);

namespace App\Application\Scheduling\Command;

use App\Application\Port\BookableSlotCommandPort;
use App\Application\Port\BookableSlotHorizonPort;
use App\Application\Port\ClockPort;
use App\Application\Port\DoctorAppointmentSettingsQueryPort;
use App\Application\Port\DoctorQueryPort;
use App\Domain\Doctor\DoctorAppointmentSettingsNotFoundException;
use App\Domain\Doctor\DoctorNotFoundException;
use App\Domain\Scheduling\BookableSlotGenerator;
use App\Domain\Shared\PractitionerId;

/** Hybrid storage: rules in settings + rolling rows in bookable_slots. */
final readonly class MaterializeBookableSlots
{
    public function __construct(
        private BookableSlotCommandPort $bookableSlots,
        private DoctorAppointmentSettingsQueryPort $appointmentSettings,
        private DoctorQueryPort $doctors,
        private BookableSlotHorizonPort $horizon,
        private ClockPort $clock,
        private BookableSlotGenerator $generator = new BookableSlotGenerator(),
    ) {
    }

    /**
     * @return array{practitioner_id: int, horizon_days: int, deleted: int, inserted: int, generated: int}
     */
    public function execute(int $practitionerId, ?int $horizonDays = null): array
    {
        $id = new PractitionerId($practitionerId);
        if ($this->doctors->find($id) === null) {
            throw new DoctorNotFoundException($id);
        }

        $settings = $this->appointmentSettings->find($id);
        if ($settings === null) {
            throw new DoctorAppointmentSettingsNotFoundException($id);
        }

        $days = $horizonDays ?? $this->horizon->horizonDays();
        $now  = $this->clock->now();

        $windows  = $this->generator->generate($settings, $now, $days);
        $earliest = $this->generator->earliestWindow($settings, $now, $days);

        $counts = $this->bookableSlots->replaceAvailableFrom(
            $id,
            $earliest['date'],
            $earliest['start_time'],
            $windows,
        );

        return [
            'practitioner_id' => $practitionerId,
            'horizon_days'    => $days,
            'deleted'         => $counts['deleted'],
            'inserted'        => $counts['inserted'],
            'generated'       => count($windows),
        ];
    }
}
