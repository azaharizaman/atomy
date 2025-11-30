<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportDestination;

/**
 * Export result value object
 * 
 * Contains the outcome of an export operation.
 * Returned by ExportManager::export() method.
 */
final readonly class ExportResult
{
    /**
     * @param bool $success Export operation success status
     * @param ExportFormat $format Output format used
     * @param ExportDestination $destination Delivery destination
     * @param string|null $filePath File path if stored (null for streaming/webhook)
     * @param int $sizeBytes Output size in bytes
     * @param int $durationMs Execution duration in milliseconds
     * @param string|null $error Error message if failed
     * @param array<string, mixed> $metadata Additional result metadata
     */
    public function __construct(
        public bool $success,
        public ExportFormat $format,
        public ExportDestination $destination,
        public ?string $filePath = null,
        public int $sizeBytes = 0,
        public int $durationMs = 0,
        public ?string $error = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if export was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if export failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get file path or throw exception
     * 
     * @throws \RuntimeException If no file path available
     */
    public function getFilePathOrFail(): string
    {
        if ($this->filePath === null) {
            throw new \RuntimeException('Export result has no file path');
        }

        return $this->filePath;
    }

    /**
     * Get error message or throw exception
     * 
     * @throws \RuntimeException If export was successful
     */
    public function getErrorOrFail(): string
    {
        if ($this->success) {
            throw new \RuntimeException('Cannot get error from successful export');
        }

        return $this->error ?? 'Unknown error';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'format' => $this->format->value,
            'destination' => $this->destination->value,
            'file_path' => $this->filePath,
            'size_bytes' => $this->sizeBytes,
            'duration_ms' => $this->durationMs,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create success result
     */
    public static function success(
        ExportFormat $format,
        ExportDestination $destination,
        ?string $filePath = null,
        int $sizeBytes = 0,
        int $durationMs = 0,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            format: $format,
            destination: $destination,
            filePath: $filePath,
            sizeBytes: $sizeBytes,
            durationMs: $durationMs,
            error: null,
            metadata: $metadata,
        );
    }

    /**
     * Create failure result
     */
    public static function failure(
        ExportFormat $format,
        ExportDestination $destination,
        string $error,
        int $durationMs = 0,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            format: $format,
            destination: $destination,
            filePath: null,
            sizeBytes: 0,
            durationMs: $durationMs,
            error: $error,
            metadata: $metadata,
        );
    }
}
