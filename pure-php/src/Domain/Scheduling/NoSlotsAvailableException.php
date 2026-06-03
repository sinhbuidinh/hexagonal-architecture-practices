<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Scheduling;

use HexagonPractise\Domain\Shared\PractitionerId;
use HexagonPractise\Domain\Shared\SlotCount;

final class NoSlotsAvailableException extends \DomainException
{
    public function __construct(
        public readonly PractitionerId $practitionerId,
        public readonly SlotCount $requested,
        public readonly SlotCount $available,
    ) {
        parent::__construct(sprintf(
            'No slots available for practitioner "%s": requested %d, available %d.',
            $practitionerId->value,
            $requested->value,
            $available->value,
        ));
    }
}
