<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Exception thrown when hydration of ScheduleRecurrence from stored data fails.
 */
class ScheduleRecurrenceHydrationException extends ReportingException
{
    public static function forMalformedData(array $data, \Throwable $previous): self
    {
        $summary = self::summarizePayload($data);

        return new self(
            "Failed to hydrate ScheduleRecurrence from stored data: " . $previous->getMessage() . " | payload=" . $summary,
            0,
            $previous
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function summarizePayload(array $data): string
    {
        $redactedKeys = ['password', 'secret', 'token', 'key', 'authorization', 'cookie'];
        $sanitized = self::sanitizeValue($data, null, $redactedKeys);

        $json = json_encode($sanitized, JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) {
            return '[unencodable payload]';
        }

        $maxLength = 300;
        if (strlen($json) <= $maxLength) {
            return $json;
        }

        return substr($json, 0, $maxLength) . '...[truncated]';
    }

    /**
     * @param mixed $value
     * @param string|null $key
     * @param array<int, string> $redactedKeys
     * @return mixed
     */
    private static function sanitizeValue(mixed $value, ?string $key, array $redactedKeys): mixed
    {
        if ($key !== null && self::isSensitiveKey($key, $redactedKeys)) {
            return '[REDACTED]';
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $childKey => $childValue) {
                $childKeyString = is_string($childKey) ? $childKey : (string) $childKey;
                $sanitized[$childKey] = self::sanitizeValue($childValue, $childKeyString, $redactedKeys);
            }
            return $sanitized;
        }

        if ($value instanceof \JsonSerializable) {
            return self::sanitizeValue($value->jsonSerialize(), $key, $redactedKeys);
        }

        if ($value instanceof \Traversable) {
            $iterableData = iterator_to_array($value, true);
            return self::sanitizeValue($iterableData, $key, $redactedKeys);
        }

        if (is_object($value)) {
            return '<object:' . $value::class . '>';
        }

        if (is_resource($value)) {
            return '<resource>';
        }

        return '<' . gettype($value) . '>';
    }

    /**
     * @param array<int, string> $redactedKeys
     */
    private static function isSensitiveKey(string $key, array $redactedKeys): bool
    {
        $lowerKey = strtolower($key);
        foreach ($redactedKeys as $needle) {
            if (str_contains($lowerKey, $needle)) {
                return true;
            }
        }

        return false;
    }
}
