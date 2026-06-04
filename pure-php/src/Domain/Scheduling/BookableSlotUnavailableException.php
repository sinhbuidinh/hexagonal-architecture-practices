<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

use HexagonPractise\Domain\Shared\BookableSlotId;

final class BookableSlotUnavailableException extends \DomainException
{
    public function __construct(
        public readonly BookableSlotId $slotId,
        public readonly string $status,
    ) {
        parent::__construct(sprintf('Bookable slot %d is not available (status: %s).', $slotId->value, $status));
    }
}
