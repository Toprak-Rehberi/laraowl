<?php

namespace App\Mcp\Support;

use Illuminate\Support\Str;

class PayloadSanitizer
{
    /**
     * Redact denylisted keys and truncate long strings in a record payload.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function sanitize(array $payload): array
    {
        /** @var array<int, string> $keys */
        $keys = config('laraowl-mcp.redaction.keys', []);
        $maxLength = (int) config('laraowl-mcp.redaction.max_length', 2000);

        return self::walk($payload, $keys, $maxLength);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<array-key, mixed>
     */
    private static function walk(array $data, array $keys, int $maxLength): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $keys, true)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::walk($value, $keys, $maxLength);
            } elseif (is_string($value)) {
                $data[$key] = Str::limit($value, $maxLength);
            }
        }

        return $data;
    }
}
