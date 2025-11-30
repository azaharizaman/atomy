<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Immutable value object representing the result of a report generation.
 */
final readonly class ReportResult
{
    /**
     * @param string $reportId The unique identifier for this generated report (ULID)
     * @param ReportFormat $format The output format
     * @param string|null $filePath Storage path (storage:// URI), null if generation failed
     * @param int $fileSize File size in bytes, 0 if generation failed
     * @param \DateTimeImmutable $generatedAt When the report was generated
     * @param int $durationMs Generation time in milliseconds
     * @param bool $isSuccessful Whether generation succeeded
     * @param string|null $error Error message if generation failed
     * @param RetentionTier $retentionTier Current storage tier (always ACTIVE for new reports)
     * @param string|null $queryResultId Reference to the Analytics query result
     */
    public function __construct(
        public string $reportId,
        public ReportFormat $format,
        public ?string $filePath,
        public int $fileSize,
        public \DateTimeImmutable $generatedAt,
        public int $durationMs,
        public bool $isSuccessful,
        public ?string $error = null,
        public RetentionTier $retentionTier = RetentionTier::ACTIVE,
        public ?string $queryResultId = null,
    ) {
        if (!$this->isSuccessful && $this->error === null) {
            throw new \InvalidArgumentException('Error message is required when generation fails');
        }

        if ($this->isSuccessful && $this->filePath === null) {
            throw new \InvalidArgumentException('File path is required for successful generation');
        }

        if ($this->fileSize < 0) {
            throw new \InvalidArgumentException('File size cannot be negative');
        }

        if ($this->durationMs < 0) {
            throw new \InvalidArgumentException('Duration cannot be negative');
        }
    }

    /**
     * Create a successful result.
     */
    public static function success(
        string $reportId,
        ReportFormat $format,
        string $filePath,
        int $fileSize,
        \DateTimeImmutable $generatedAt,
        int $durationMs,
        ?string $queryResultId = null
    ): self {
        return new self(
            reportId: $reportId,
            format: $format,
            filePath: $filePath,
            fileSize: $fileSize,
            generatedAt: $generatedAt,
            durationMs: $durationMs,
            isSuccessful: true,
            error: null,
            retentionTier: RetentionTier::ACTIVE,
            queryResultId: $queryResultId
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(
        string $reportId,
        ReportFormat $format,
        \DateTimeImmutable $generatedAt,
        int $durationMs,
        string $error
    ): self {
        return new self(
            reportId: $reportId,
            format: $format,
            filePath: null,
            fileSize: 0,
            generatedAt: $generatedAt,
            durationMs: $durationMs,
            isSuccessful: false,
            error: $error
        );
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->reportId,
            'format' => $this->format->value,
            'file_path' => $this->filePath,
            'file_size_bytes' => $this->fileSize,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'duration_ms' => $this->durationMs,
            'is_successful' => $this->isSuccessful,
            'error' => $this->error,
            'retention_tier' => $this->retentionTier->value,
            'query_result_id' => $this->queryResultId,
        ];
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->fileSize;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get human-readable duration.
     */
    public function getFormattedDuration(): string
    {
        if ($this->durationMs < 1000) {
            return $this->durationMs . ' ms';
        }

        $seconds = round($this->durationMs / 1000, 2);
        return $seconds . ' s';
    }
}
