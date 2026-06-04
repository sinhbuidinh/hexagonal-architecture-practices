<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Infrastructure;

use HexagonPractise\Infrastructure\Scheduling\ClinicLunchBreakFromConfig;
use PHPUnit\Framework\TestCase;

final class ClinicLunchBreakFromConfigTest extends TestCase
{
    public function testFromConfigArrayUsesClinicLunchBreakEnvKeys(): void
    {
        $port = ClinicLunchBreakFromConfig::fromConfigArray([
            'clinic_lunch_break_enabled' => true,
            'clinic_lunch_break_start'   => '12:00',
            'clinic_lunch_break_end'     => '13:30',
        ]);

        $break = $port->lunchBreak();

        $this->assertNotNull($break);
        $this->assertSame('12:00', $break->startTime);
        $this->assertSame('13:30', $break->endTime);
    }

    public function testFromConfigArrayCanDisableLunchBreak(): void
    {
        $port = ClinicLunchBreakFromConfig::fromConfigArray([
            'clinic_lunch_break_enabled' => false,
        ]);

        $this->assertNull($port->lunchBreak());
    }

    public function testFromConfigArrayParsesStringEnabledFlag(): void
    {
        $port = ClinicLunchBreakFromConfig::fromConfigArray([
            'clinic_lunch_break_enabled' => 'false',
        ]);

        $this->assertNull($port->lunchBreak());
    }
}
