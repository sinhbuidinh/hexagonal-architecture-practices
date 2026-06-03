<?php

declare(strict_types=1);

namespace HexagonPractise\Tests\Unit\Domain;

use HexagonPractise\Domain\Audit\AuditSensitiveDataSanitizer;
use HexagonPractise\Domain\Audit\AuditStateDiff;
use PHPUnit\Framework\TestCase;

final class AuditSensitiveDataSanitizerTest extends TestCase
{
    private AuditSensitiveDataSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new AuditSensitiveDataSanitizer();
    }

    public function testRedactsClinicalNarrativeAndCredentials(): void
    {
        $safe = $this->sanitizer->sanitize([
            'status'         => 'active',
            'instructions'   => 'Take twice daily with food',
            'pharmacy_notes' => 'Counter 2',
            'password'       => 'secret',
            'ssn'            => '123-45-6789',
        ]);

        $this->assertSame('active', $safe['status']);
        $this->assertSame('[REDACTED]', $safe['instructions']);
        $this->assertSame('[REDACTED]', $safe['pharmacy_notes']);
        $this->assertSame('[REDACTED]', $safe['password']);
        $this->assertSame('[REDACTED]', $safe['ssn']);
    }

    public function testStateDiffShowsFieldTransitions(): void
    {
        $diff = (new AuditStateDiff())->format(
            ['status' => 'scheduled'],
            ['status' => 'cancelled'],
        );

        $this->assertSame('status: "scheduled" -> "cancelled"', $diff);
    }
}
