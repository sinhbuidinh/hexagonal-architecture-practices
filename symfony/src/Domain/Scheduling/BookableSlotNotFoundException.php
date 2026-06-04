<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Shared\BookableSlotId;

final class BookableSlotNotFoundException extends \DomainException
{
    public function __construct(public readonly BookableSlotId $slotId)
    {
        parent::__construct(sprintf('Bookable slot %d not found.', $slotId->value));
    }
}
