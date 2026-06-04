<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Shared\BookableSlotId;

final class BookableSlotUnavailableException extends \DomainException
{
    public function __construct(
        public readonly BookableSlotId $slotId,
        public readonly string $status,
    ) {
        parent::__construct(sprintf('Bookable slot %d is not available (status: %s).', $slotId->value, $status));
    }
}
