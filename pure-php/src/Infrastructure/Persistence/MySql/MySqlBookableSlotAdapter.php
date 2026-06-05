<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\MySql;

use Doctrine\DBAL\Connection;
use HexagonPractise\Application\Port\BookableSlotCommandPort;
use HexagonPractise\Application\Port\BookableSlotQueryPort;
use HexagonPractise\Domain\Scheduling\BookableSlot;
use HexagonPractise\Domain\Scheduling\BookableSlotNotFoundException;
use HexagonPractise\Domain\Scheduling\BookableSlotUnavailableException;
use HexagonPractise\Domain\Shared\AppointmentId;
use HexagonPractise\Domain\Shared\BookableSlotId;
use HexagonPractise\Domain\Shared\PractitionerId;

final class MySqlBookableSlotAdapter implements BookableSlotCommandPort, BookableSlotQueryPort
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function publish(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
        string $endTime,
    ): BookableSlot {
        $this->assertCanPublish($practitionerId, [
            ['date' => $date, 'start_time' => $startTime, 'end_time' => $endTime],
        ]);

        $now = DatabaseConnectionFactory::now();

        $this->connection->insert(table: 'bookable_slots', data : [
            'practitioner_id' => $practitionerId->value,
            'slot_date'       => $date,
            'starts_at'       => $this->toSqlTime($startTime),
            'ends_at'         => $this->toSqlTime($endTime),
            'status'          => BookableSlot::STATUS_AVAILABLE,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $id = (int) $this->connection->lastInsertId();

        return $this->mapRow(row: [
            'id'              => $id,
            'practitioner_id' => $practitionerId->value,
            'slot_date'       => $date,
            'starts_at'       => $this->toSqlTime($startTime),
            'ends_at'         => $this->toSqlTime($endTime),
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

        return $this->connection->transactional(function () use ($practitionerId, $date, $startTime, $windows): array {
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

        $now      = DatabaseConnectionFactory::now();
        $inserted = 0;

        foreach (array_chunk($windows, 200) as $chunk) {
            foreach ($chunk as $window) {
                $this->connection->insert(table: 'bookable_slots', data : [
                    'practitioner_id' => $practitionerId->value,
                    'slot_date'       => (string) $window['date'],
                    'starts_at'       => $this->toSqlTime((string) $window['start_time']),
                    'ends_at'         => $this->toSqlTime((string) $window['end_time']),
                    'status'          => BookableSlot::STATUS_AVAILABLE,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                ++$inserted;
            }
        }

        return $inserted;
    }

    public function deleteAvailableStartingAfter(
        PractitionerId $practitionerId,
        string $date,
        string $startTime,
    ): int {
        return $this->connection->executeStatement(
            sql   : <<<'SQL'
            DELETE FROM bookable_slots
            WHERE practitioner_id = ?
              AND status = ?
              AND (
                  slot_date > ?
                  OR (slot_date = ? AND starts_at >= ?)
              )
            SQL,
            params: [
                $practitionerId->value,
                BookableSlot::STATUS_AVAILABLE,
                $date,
                $date,
                $this->toSqlTime($startTime),
            ],
        );
    }

    public function listAvailable(
        PractitionerId $practitionerId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(table: 'bookable_slots')
            ->where(predicate: 'practitioner_id = :practitioner_id')
            ->andWhere(predicate: 'status = :status')
            ->orderBy(sort: 'slot_date', order: 'ASC')
            ->addOrderBy(sort: 'starts_at', order: 'ASC')
            ->setParameter(key: 'practitioner_id', value: $practitionerId->value)
            ->setParameter(key: 'status', value: BookableSlot::STATUS_AVAILABLE);

        if ($dateFrom !== null) {
            $qb->andWhere(predicate: 'slot_date >= :date_from')->setParameter(key: 'date_from', value: $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere(predicate: 'slot_date <= :date_to')->setParameter(key: 'date_to', value: $dateTo);
        }

        return array_map(
            callback: fn (array $row): BookableSlot => $this->mapRow(row: $row),
            array   : $qb->fetchAllAssociative(),
        );
    }

    public function find(BookableSlotId $slotId): ?BookableSlot
    {
        $row = $this->connection->fetchAssociative(
            query : 'SELECT * FROM bookable_slots WHERE id = ?',
            params: [$slotId->value],
        );

        return $row !== false ? $this->mapRow(row: $row) : null;
    }

    public function listWindowsBetween(
        PractitionerId $practitionerId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $rows = $this->connection->fetchAllAssociative(
            query : <<<'SQL'
            SELECT slot_date, starts_at, ends_at
            FROM bookable_slots
            WHERE practitioner_id = ?
              AND slot_date BETWEEN ? AND ?
            ORDER BY slot_date, starts_at
            SQL,
            params: [$practitionerId->value, $dateFrom, $dateTo],
        );

        return array_map(
            callback: static fn (array $row): array => [
                'date'       => (string) $row['slot_date'],
                'start_time' => substr((string) $row['starts_at'], 0, 5),
                'end_time'   => substr((string) $row['ends_at'], 0, 5),
            ],
            array   : $rows,
        );
    }

    public function markHeld(BookableSlotId $slotId, AppointmentId $appointmentId): void
    {
        $this->connection->transactional(function () use ($slotId): void {
            $row = $this->connection->createQueryBuilder()
                ->select('*')
                ->from(table: 'bookable_slots')
                ->where(predicate: 'id = :id')
                ->setParameter(key: 'id', value: $slotId->value)
                ->forUpdate()
                ->fetchAssociative();

            if ($row === false) {
                throw new BookableSlotNotFoundException($slotId);
            }

            if ($row['status'] !== BookableSlot::STATUS_AVAILABLE) {
                throw new BookableSlotUnavailableException($slotId, (string) $row['status']);
            }

            $this->connection->update(
                table   : 'bookable_slots',
                data    : [
                    'status'     => BookableSlot::STATUS_HELD,
                    'updated_at' => DatabaseConnectionFactory::now(),
                ],
                criteria: ['id' => $slotId->value],
            );
        });
    }

    public function release(BookableSlotId $slotId): void
    {
        $updated = $this->connection->update(
            table   : 'bookable_slots',
            data    : [
                'status'     => BookableSlot::STATUS_AVAILABLE,
                'updated_at' => DatabaseConnectionFactory::now(),
            ],
            criteria: ['id' => $slotId->value],
        );

        if ($updated === 0) {
            throw new BookableSlotNotFoundException($slotId);
        }
    }

    public function markConfirmed(BookableSlotId $slotId): void
    {
        $updated = $this->connection->update(
            table   : 'bookable_slots',
            data    : [
                'status'     => BookableSlot::STATUS_CONFIRMED,
                'updated_at' => DatabaseConnectionFactory::now(),
            ],
            criteria: ['id' => $slotId->value],
        );

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

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): BookableSlot
    {
        return new BookableSlot(
            id            : new BookableSlotId((int) $row['id']),
            practitionerId: new PractitionerId((int) $row['practitioner_id']),
            date          : (string) $row['slot_date'],
            startTime     : substr((string) $row['starts_at'], 0, 5),
            endTime       : substr((string) $row['ends_at'], 0, 5),
            status        : (string) $row['status'],
        );
    }

    private function toSqlTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}
