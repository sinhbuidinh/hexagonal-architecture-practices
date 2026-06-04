<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Scheduling;

use HexagonPractise\Application\Port\ClinicLunchBreakPort;
use HexagonPractise\Domain\Scheduling\ClinicLunchBreak;

final readonly class ClinicLunchBreakFromConfig implements ClinicLunchBreakPort
{
    public function __construct(
        private bool $enabled = true,
        private string $startTime = '12:00',
        private string $endTime = '13:30',
    ) {
    }

    /** @param array<string, mixed> $config */
    public static function fromConfigArray(array $config): self
    {
        $enabled = $config['clinic_lunch_break_enabled'] ?? true;
        if (is_string($enabled)) {
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOL);
        }

        return new self(
            enabled  : (bool) $enabled,
            startTime: (string) ($config['clinic_lunch_break_start'] ?? '12:00'),
            endTime  : (string) ($config['clinic_lunch_break_end'] ?? '13:30'),
        );
    }

    public function lunchBreak(): ?ClinicLunchBreak
    {
        if (!$this->enabled) {
            return null;
        }

        return new ClinicLunchBreak($this->startTime, $this->endTime);
    }
}
