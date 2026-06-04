<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Audit;

/**
 * Strips or masks values that must never appear in audit logs (SSN, cards, psychotherapy narrative, passwords).
 */
final class AuditSensitiveDataSanitizer
{
    private const REDACTED = '[REDACTED]';

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

    /** @var list<string> */
    private const MASKABLE_KEY_FRAGMENTS = [
        'ssn',
        'social_security',
        'credit_card',
        'card_number',
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
            $normalizedKey = $this->normalizeKey((string) $key);

            if ($this->isRedactedKey($normalizedKey)) {
                $out[$key] = is_string($value) && $this->isMaskableKey($normalizedKey)
                    ? $this->maskSensitiveScalar($value)
                    : self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->sanitize($value);

                continue;
            }

            if (is_string($value) && $this->looksLikeSensitiveScalar($value)) {
                $out[$key] = $this->maskSensitiveScalar($value);

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Mask SSN / card patterns inside free-text (e.g. exception messages), keeping last 4 digits.
     */
    public function sanitizeMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        // \b(\d{3})-(\d{2})-(\d{4})\b — US SSN anywhere in the string.
        // Example: "Patient SSN 123-45-6789 invalid" → "Patient SSN ###-##-6789 invalid"
        $message = (string) preg_replace_callback(
            '/\b(\d{3})-(\d{2})-(\d{4})\b/',
            static fn (array $matches): string => '###-##-'.$matches[3],
            $message,
        );

        // \b(\d{4})([\s-]?)(\d{4})\2(\d{4})\2(\d{4})\b — PAN; keep last 4, mask the rest.
        // Example: "Card 4111-1111-1111-1111" → "Card ####-####-####-1111"
        $message = (string) preg_replace_callback(
            '/\b(\d{4})([\s-]?)(\d{4})\2(\d{4})\2(\d{4})\b/',
            function (array $matches): string {
                $separator = $matches[2];

                return $separator === ''
                    ? '############'.$matches[5]
                    : '####'.$separator.'####'.$separator.'####'.$separator.$matches[5];
            },
            $message,
        );

        return $message;
    }

    /**
     * Key-based redaction: field name matches REDACTED_KEYS (exact or substring after normalizing hyphens).
     *
     * SSN/card keys are masked (last 4 visible); narrative and secrets stay [REDACTED].
     */
    private function isRedactedKey(string $normalizedKey): bool
    {
        foreach (self::REDACTED_KEYS as $blocked) {
            if ($normalizedKey === $blocked || str_contains($normalizedKey, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function isMaskableKey(string $normalizedKey): bool
    {
        foreach (self::MASKABLE_KEY_FRAGMENTS as $fragment) {
            if ($normalizedKey === $fragment || str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(str_replace('-', '_', $key));
    }

    /**
     * Whole string value looks like SSN or card (used by sanitize() for non-blocked keys).
     */
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

    private function maskSensitiveScalar(string $value): string
    {
        if (preg_match('/^\d{3}-\d{2}-(\d{4})$/', $value, $matches) === 1) {
            return '###-##-'.$matches[1];
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (preg_match('/^\d{13,19}$/', $digits) === 1) {
            return $this->maskCardDigits($digits, $value);
        }

        return self::REDACTED;
    }

    private function maskCardDigits(string $digits, string $original): string
    {
        $last4 = substr($digits, -4);

        if (preg_match('/\d{4}([\s-])\d{4}\1\d{4}\1\d{4}/', $original, $matches) === 1) {
            $separator = $matches[1];

            return '####'.$separator.'####'.$separator.'####'.$separator.$last4;
        }

        if (preg_match('/\d{4}\s\d{4}\s\d{4}\s\d{4}/', $original) === 1) {
            return '#### #### #### '.$last4;
        }

        return '####-####-####-'.$last4;
    }
}
