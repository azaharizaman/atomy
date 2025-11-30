<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Thrown when report distribution via Notifier fails.
 */
class ReportDistributionException extends ReportingException
{
    /**
     * Create exception for notification send failure.
     */
    public static function notificationFailed(
        string $recipientId,
        \Throwable $previous
    ): self {
        return new self(
            "Failed to send notification to recipient '{$recipientId}': {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for batch distribution failure.
     */
    public static function batchFailed(
        int $failureCount,
        int $totalCount
    ): self {
        return new self(
            "Batch distribution failed: {$failureCount} out of {$totalCount} recipients failed"
        );
    }

    /**
     * Create exception for missing report file.
     */
    public static function missingReportFile(string $filePath): self
    {
        return new self(
            "Cannot distribute report: file not found at '{$filePath}'"
        );
    }

    /**
     * Create exception for invalid recipient.
     */
    public static function invalidRecipient(string $recipientId): self
    {
        return new self(
            "Invalid recipient: '{$recipientId}' does not implement NotifiableInterface"
        );
    }
}
