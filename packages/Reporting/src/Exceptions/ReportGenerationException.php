<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Thrown when report generation fails due to Analytics or Export errors.
 */
class ReportGenerationException extends ReportingException
{
    /**
     * Create exception for Analytics query execution failure.
     */
    public static function queryExecutionFailed(
        string $queryId,
        \Throwable $previous
    ): self {
        return new self(
            "Failed to execute Analytics query '{$queryId}': {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for Export rendering failure.
     */
    public static function exportFailed(
        string $format,
        \Throwable $previous
    ): self {
        return new self(
            "Failed to generate {$format} export: {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for template loading failure.
     */
    public static function templateLoadFailed(
        string $templateId,
        \Throwable $previous
    ): self {
        return new self(
            "Failed to load template '{$templateId}': {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for storage failure.
     */
    public static function storageFailed(
        string $filePath,
        \Throwable $previous
    ): self {
        return new self(
            "Failed to store report at '{$filePath}': {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for timeout.
     */
    public static function timeout(int $timeoutSeconds): self
    {
        return new self(
            "Report generation timed out after {$timeoutSeconds} seconds"
        );
    }
}
