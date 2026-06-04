<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

final class OverlappingBookableWindowException extends \DomainException
{
    /**
     * @param array{date: string, start_time: string, end_time: string} $incoming
     * @param array{date: string, start_time: string, end_time: string} $conflictsWith
     */
    public function __construct(
        public readonly array $incoming,
        public readonly array $conflictsWith,
    ) {
        parent::__construct(sprintf(
            'Bookable window %s %s–%s overlaps %s %s–%s.',
            $incoming['date'],
            $incoming['start_time'],
            $incoming['end_time'],
            $conflictsWith['date'],
            $conflictsWith['start_time'],
            $conflictsWith['end_time'],
        ));
    }
}
