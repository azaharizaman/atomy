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
        $sanitized = [];

        foreach ($data as $key => $value) {
            $keyString = (string) $key;
            $lowerKey = strtolower($keyString);
            $isSensitive = false;
            foreach ($redactedKeys as $needle) {
                if (str_contains($lowerKey, $needle)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$keyString] = '[REDACTED]';
                continue;
            }

            $sanitized[$keyString] = $value;
        }

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
}
