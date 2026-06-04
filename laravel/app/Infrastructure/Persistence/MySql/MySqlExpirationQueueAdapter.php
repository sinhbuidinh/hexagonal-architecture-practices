<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql;

use App\Application\Port\ExpirationQueuePort;
use App\Domain\Expiration\ExpiringItem;
use Illuminate\Support\Facades\DB;

final class MySqlExpirationQueueAdapter implements ExpirationQueuePort
{
    public function schedule(ExpiringItem $item): void
    {
        DB::table('expiration_queue')->updateOrInsert(
            attributes: ['id' => $item->id],
            values    : [
                'payload'    => json_encode($item->payload),
                'expires_at' => $item->expiresAt->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function cancel(string $itemId): void
    {
        DB::table('expiration_queue')->where(column: 'id', operator: '=', value: $itemId)->delete();
    }

    public function pollDue(\DateTimeImmutable $now, int $limit = 100): array
    {
        if ($limit <= 0) {
            return [];
        }

        return DB::transaction(callback: function () use ($now, $limit): array {
            $rows = DB::table('expiration_queue')
                ->where(column: 'expires_at', operator: '<=', value: $now->format('Y-m-d H:i:s'))
                ->orderBy(column: 'expires_at')
                ->limit(value: $limit)
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $ids = $rows->pluck(value: 'id')->all();
            DB::table('expiration_queue')->whereIn(column: 'id', values: $ids)->delete();

            return $rows->map(callback: fn (object $row): ExpiringItem => new ExpiringItem(
                id       : $row->id,
                payload  : json_decode($row->payload, true),
                expiresAt: new \DateTimeImmutable($row->expires_at),
            ))->all();
        });
    }
}
