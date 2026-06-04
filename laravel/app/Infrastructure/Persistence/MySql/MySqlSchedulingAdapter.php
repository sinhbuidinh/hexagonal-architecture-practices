<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\SchedulingCommandPort;
use App\Application\Port\SchedulingQueryPort;
use App\Domain\Scheduling\AppointmentHold;
use App\Domain\Scheduling\AppointmentNotFoundException;
use App\Domain\Scheduling\NoSlotsAvailableException;
use App\Domain\Shared\AppointmentId;
use App\Domain\Shared\BookableSlotId;
use App\Domain\Shared\PatientId;
use App\Domain\Shared\PractitionerId;
use App\Domain\Shared\SlotCount;
use Illuminate\Support\Facades\DB;

final class MySqlSchedulingAdapter implements SchedulingCommandPort, SchedulingQueryPort
{
    public function setAvailability(PractitionerId $practitionerId, SlotCount $slots): void
    {
        DB::table('practitioner_availability')->updateOrInsert(
            attributes: ['practitioner_id' => $practitionerId->value],
            values    : ['slots' => $slots->value],
        );
    }

    public function availableSlots(PractitionerId $practitionerId): SlotCount
    {
        $row = DB::table('practitioner_availability')
            ->where(column: 'practitioner_id', operator: '=', value: $practitionerId->value)
            ->first();

        return new SlotCount($row !== null ? (int) $row->slots : 0);
    }

    public function hold(AppointmentHold $hold): AppointmentHold
    {
        return DB::transaction(callback: function () use ($hold): AppointmentHold {
            if ($hold->bookableSlotId !== null) {
                $slotRow = DB::table('bookable_slots')
                    ->where('id', $hold->bookableSlotId->value)
                    ->lockForUpdate()
                    ->first();

                if ($slotRow === null) {
                    throw new \App\Domain\Scheduling\BookableSlotNotFoundException($hold->bookableSlotId);
                }

                if ($slotRow->status !== \App\Domain\Scheduling\BookableSlot::STATUS_AVAILABLE) {
                    throw new \App\Domain\Scheduling\BookableSlotUnavailableException(
                        $hold->bookableSlotId,
                        (string) $slotRow->status,
                    );
                }

                DB::table('bookable_slots')->where('id', $hold->bookableSlotId->value)->update([
                    'status'     => \App\Domain\Scheduling\BookableSlot::STATUS_HELD,
                    'updated_at' => now(),
                ]);
            } else {
                $row = DB::table('practitioner_availability')
                    ->where(column: 'practitioner_id', operator: '=', value: $hold->practitionerId->value)
                    ->lockForUpdate()
                    ->first();

                $available      = $row !== null ? (int) $row->slots : 0;
                $availableSlots = new SlotCount($available);

                if (!$availableSlots->isGreaterOrEqual($hold->slots)) {
                    throw new NoSlotsAvailableException(
                        practitionerId: $hold->practitionerId,
                        requested     : $hold->slots,
                        available     : $availableSlots,
                    );
                }

                DB::table('practitioner_availability')->updateOrInsert(
                    attributes: ['practitioner_id' => $hold->practitionerId->value],
                    values    : ['slots' => $available - $hold->slots->value],
                );
            }

            $id = DB::table('appointment_holds')->insertGetId(values: [
                'practitioner_id'   => $hold->practitionerId->value,
                'bookable_slot_id'  => $hold->bookableSlotId?->value,
                'patient_id'        => $hold->patientId->value,
                'slots'             => $hold->slots->value,
                'expires_at'        => $hold->expiresAt->format('Y-m-d H:i:s'),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return new AppointmentHold(
                id            : new AppointmentId((string) $id),
                practitionerId: $hold->practitionerId,
                patientId     : $hold->patientId,
                slots         : $hold->slots,
                expiresAt     : $hold->expiresAt,
                bookableSlotId: $hold->bookableSlotId,
            );
        });
    }

    public function cancelHold(AppointmentId $appointmentId): void
    {
        DB::transaction(callback: function () use ($appointmentId): void {
            $hold = DB::table('appointment_holds')
                ->where(column: 'id', operator: '=', value: $appointmentId->value)
                ->lockForUpdate()
                ->first();

            if ($hold === null) {
                throw new AppointmentNotFoundException($appointmentId);
            }

            if ($hold->bookable_slot_id === null) {
                $availability = DB::table('practitioner_availability')
                    ->where(column: 'practitioner_id', operator: '=', value: $hold->practitioner_id)
                    ->lockForUpdate()
                    ->first();

                $currentSlots = $availability !== null ? (int) $availability->slots : 0;

                DB::table('practitioner_availability')->updateOrInsert(
                    attributes: ['practitioner_id' => $hold->practitioner_id],
                    values    : ['slots' => $currentSlots + (int) $hold->slots],
                );
            }

            DB::table('appointment_holds')->where(column: 'id', operator: '=', value: $appointmentId->value)->delete();
        });
    }

    public function confirm(AppointmentId $appointmentId): void
    {
        $deleted = DB::table('appointment_holds')->where(column: 'id', operator: '=', value: $appointmentId->value)->delete();
        if ($deleted === 0) {
            throw new AppointmentNotFoundException($appointmentId);
        }
    }

    public function findHold(AppointmentId $appointmentId): ?AppointmentHold
    {
        $row = DB::table('appointment_holds')->where(column: 'id', operator: '=', value: $appointmentId->value)->first();
        if ($row === null) {
            return null;
        }

        return new AppointmentHold(
            id            : new AppointmentId((string) $row->id),
            practitionerId: new PractitionerId((int) $row->practitioner_id),
            patientId     : new PatientId((string) $row->patient_id),
            slots         : new SlotCount((int) $row->slots),
            expiresAt     : new \DateTimeImmutable($row->expires_at),
            bookableSlotId: $row->bookable_slot_id !== null
                ? new BookableSlotId((int) $row->bookable_slot_id)
                : null,
        );
    }
}
