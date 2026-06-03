<?php

declare(strict_types=1);

namespace HexagonPractise\Domain\Audit;

final class AuditStateDiff
{
    /**
     * @param array<string, mixed>|null $before sanitized
     * @param array<string, mixed>|null $after sanitized
     */
    public function format(?array $before, ?array $after): ?string
    {
        if ($before === null && $after === null) {
            return null;
        }

        if ($before === null) {
            return 'created: ' . $this->flatten($after ?? []);
        }

        if ($after === null) {
            return 'removed: ' . $this->flatten($before);
        }

        $lines = [];
        $keys  = array_unique([...array_keys($before), ...array_keys($after)]);
        sort($keys);

        foreach ($keys as $key) {
            $old     = $before[$key] ?? null;
            $new     = $after[$key] ?? null;
            if ($old === $new) {
                continue;
            }

            $lines[] = sprintf(
                '%s: %s -> %s',
                $key,
                $this->scalar($old),
                $this->scalar($new),
            );
        }

        return $lines === [] ? null : implode('; ', $lines);
    }

    /** @param array<string, mixed> $state */
    private function flatten(array $state): string
    {
        $parts = [];
        foreach ($state as $key => $value) {
            $parts[] = $key . '=' . $this->scalar($value);
        }

        return implode(', ', $parts);
    }

    private function scalar(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return json_encode((string) $value, JSON_THROW_ON_ERROR);
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
