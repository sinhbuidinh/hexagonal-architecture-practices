<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Audit;

/**
 * Strips values that must never appear in audit logs (SSN, cards, psychotherapy narrative, passwords).
 */
final class AuditSensitiveDataSanitizer
{
    private const REDACTED      = '[REDACTED]';

    /** @var list<string> */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'ssn',
        'social_security',
        'social_security_number',
        'credit_card',
        'card_number',
        'cvv',
        'psychotherapy_notes',
        'instructions',
        'pharmacy_notes',
    ];

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>|null
     */
    public function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $out = [];
        foreach ($data as $key => $value) {
            if ($this->isRedactedKey((string) $key)) {
                $out[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->sanitize($value);

                continue;
            }

            if (is_string($value) && $this->looksLikeSensitiveScalar($value)) {
                $out[$key] = self::REDACTED;

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    public function sanitizeMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        $message = (string) preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', self::REDACTED, $message);
        $message = (string) preg_replace('/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', self::REDACTED, $message);

        return $message;
    }

    private function isRedactedKey(string $key): bool
    {
        $normalized = strtolower(str_replace('-', '_', $key));

        foreach (self::REDACTED_KEYS as $blocked) {
            if ($normalized === $blocked || str_contains($normalized, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSensitiveScalar(string $value): bool
    {
        if (preg_match('/^\d{3}-\d{2}-\d{4}$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^\d{13,19}$/', preg_replace('/\D/', '', $value) ?? '') === 1) {
            return true;
        }

        return false;
    }
}
