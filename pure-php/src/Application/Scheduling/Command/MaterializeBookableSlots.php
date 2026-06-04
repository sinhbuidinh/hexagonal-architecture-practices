<?php

declare(strict_types=1);

namespace HexagonPractise\Application\Scheduling\Command;

use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\BookableSlotHorizonPort;
use HexagonPractise\Application\Port\ClockPort;
use HexagonPractise\Application\Port\DoctorAppointmentSettingsQueryPort;
use HexagonPractise\Application\Port\DoctorQueryPort;
use HexagonPractise\Domain\Doctor\DoctorAppointmentSettingsNotFoundException;
use HexagonPractise\Domain\Doctor\DoctorNotFoundException;
use HexagonPractise\Domain\Scheduling\BookableSlotGenerator;
use HexagonPractise\Domain\Shared\PractitionerId;

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
            practitionerId: $id,
            date          : $earliest['date'],
            startTime     : $earliest['start_time'],
            windows       : $windows,
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
