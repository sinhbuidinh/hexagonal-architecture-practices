<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\BookableSlotCommandPort;
use App\Application\Port\BookableSlotQueryPort;
use App\Domain\Scheduling\BookableSlot;
use App\Domain\Scheduling\BookableSlotNotFoundException;
use App\Domain\Scheduling\BookableSlotUnavailableException;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\BookableSlotId;
use App\Domain\Shared\PractitionerId;
use Illuminate\Support\Facades\DB;

final class MySqlBookableSlotAdapter implements BookableSlotCommandPort, BookableSlotQueryPort
{
    public function publish(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        string $endTime,
    ): BookableSlot {
        $this->assertCanPublish($practitionerId, [
            ['date' => $date, 'start_time' => $startTime, 'end_time' => $endTime],
        ]);

        $id = DB::table('bookable_slots')->insertGetId(values: [
            'practitioner_id' => $practitionerId->value,
            'slot_date'       => $date,
            'starts_at'       => $startTime . ':00',
            'ends_at'         => $endTime . ':00',
            'status'          => BookableSlot::STATUS_AVAILABLE,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return $this->mapRow((object) [
            'id'              => $id,
            'practitioner_id' => $practitionerId->value,
            'slot_date'       => $date,
            'starts_at'       => $startTime . ':00',
            'ends_at'         => $endTime . ':00',
            'status'          => BookableSlot::STATUS_AVAILABLE,
        ]);
    }

    public function replaceAvailableFrom(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        array $windows,
    ): array {
        BookableSlot::assertNoOverlapWithinBatch($windows);

        return DB::transaction(function () use ($practitionerId, $date, $startTime, $windows): array {
            return [
                'deleted'  => $this->deleteAvailableStartingAfter($practitionerId, $date, $startTime),
                'inserted' => $this->publishMany($practitionerId, $windows),
            ];
        });
    }

    public function publishMany(PractitionerId $practitionerId, array $windows): int
    {
        if ($windows === []) {
            return 0;
        }

        $this->assertCanPublish($practitionerId, $windows);

        $now      = now();
        $inserted = 0;

        foreach (array_chunk($windows, 200) as $chunk) {
            $rows = [];
            foreach ($chunk as $window) {
                $rows[] = [
                    'practitioner_id' => $practitionerId->value,
                    'slot_date'       => (string) $window['date'],
                    'starts_at'       => (string) $window['start_time'] . ':00',
                    'ends_at'         => (string) $window['end_time'] . ':00',
                    'status'          => BookableSlot::STATUS_AVAILABLE,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            $inserted += DB::table('bookable_slots')->insert($rows);
        }

        return $inserted;
    }

    public function deleteAvailableStartingAfter(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
    ): int {
        return DB::table('bookable_slots')
            ->where('practitioner_id', $practitionerId->value)
            ->where('status', BookableSlot::STATUS_AVAILABLE)
            ->where(function ($query) use ($date, $startTime): void {
                $query->where('slot_date', '>', $date)
                    ->orWhere(function ($inner) use ($date, $startTime): void {
                        $inner->where('slot_date', $date)
                            ->where('starts_at', '>=', $startTime . ':00');
                    });
            })
            ->delete();
    }

    public function listAvailable(
        PractitionerId $practitionerId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $query = DB::table('bookable_slots')
            ->where('practitioner_id', $practitionerId->value)
            ->where('status', BookableSlot::STATUS_AVAILABLE)
            ->orderBy('slot_date')
            ->orderBy('starts_at');

        if ($dateFrom !== null) {
            $query->where('slot_date', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('slot_date', '<=', $dateTo);
        }

        return array_map(
            fn (object $row): BookableSlot => $this->mapRow($row),
            $query->get()->all(),
        );
    }

    public function find(BookableSlotId $slotId): ?BookableSlot
    {
        $row = DB::table('bookable_slots')->where('id', $slotId->value)->first();

        return $row !== null ? $this->mapRow($row) : null;
    }

    public function listWindowsBetween(
        PractitionerId $practitionerId,
        string $dateFrom,
        string $dateTo,
    ): array {
        return DB::table('bookable_slots')
            ->where('practitioner_id', $practitionerId->value)
            ->whereBetween('slot_date', [$dateFrom, $dateTo])
            ->orderBy('slot_date')
            ->orderBy('starts_at')
            ->get(['slot_date', 'starts_at', 'ends_at'])
            ->map(static fn (object $row): array => [
                'date'       => (string) $row->slot_date,
                'start_time' => substr((string) $row->starts_at, 0, 5),
                'end_time'   => substr((string) $row->ends_at, 0, 5),
            ])
            ->all();
    }

    public function markHeld(BookableSlotId $slotId, AppointmentId $appointmentId): void
    {
        DB::transaction(function () use ($slotId): void {
            $row = DB::table('bookable_slots')->where('id', $slotId->value)->lockForUpdate()->first();
            if ($row === null) {
                throw new BookableSlotNotFoundException($slotId);
            }

            if ($row->status !== BookableSlot::STATUS_AVAILABLE) {
                throw new BookableSlotUnavailableException($slotId, (string) $row->status);
            }

            DB::table('bookable_slots')->where('id', $slotId->value)->update([
                'status'     => BookableSlot::STATUS_HELD,
                'updated_at' => now(),
            ]);
        });
    }

    public function release(BookableSlotId $slotId): void
    {
        $updated = DB::table('bookable_slots')->where('id', $slotId->value)->update([
            'status'     => BookableSlot::STATUS_AVAILABLE,
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            throw new BookableSlotNotFoundException($slotId);
        }
    }

    public function markConfirmed(BookableSlotId $slotId): void
    {
        $updated = DB::table('bookable_slots')->where('id', $slotId->value)->update([
            'status'     => BookableSlot::STATUS_CONFIRMED,
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            throw new BookableSlotNotFoundException($slotId);
        }
    }

    /**
     * @param list<array{date: string, start_time: string, end_time: string}> $windows
     */
    private function assertCanPublish(PractitionerId $practitionerId, array $windows): void
    {
        BookableSlot::assertNoOverlapWithinBatch($windows);

        $bounds   = BookableSlot::dateBounds($windows);
        $existing = $this->listWindowsBetween($practitionerId, $bounds['min'], $bounds['max']);

        BookableSlot::assertNoOverlapWithExisting($windows, $existing);
    }

    private function mapRow(object $row): BookableSlot
    {
        return new BookableSlot(
            id            : new BookableSlotId((int) $row->id),
            practitionerId: new PractitionerId((int) $row->practitioner_id),
            date          : (string) $row->slot_date,
            startTime     : substr((string) $row->starts_at, 0, 5),
            endTime       : substr((string) $row->ends_at, 0, 5),
            status        : (string) $row->status,
        );
    }
}
