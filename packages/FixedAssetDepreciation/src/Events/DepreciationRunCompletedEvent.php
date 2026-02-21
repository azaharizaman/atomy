<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;
use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;

/**
 * Depreciation Run Completed Event
 *
 * Dispatched when a batch depreciation run has completed.
 * Severity: HIGH
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class DepreciationRunCompletedEvent
{
    public function __construct(
        public string $runId,
        public string $tenantId,
        public string $periodId,
        public int $processedCount,
        public int $errorCount,
        public float $totalDepreciation,
        public string $currency,
        public DateTimeInterface $runDate,
        public DateTimeInterface $completedDate,
        public bool $postedToGL = false,
        public array $context = [],
    ) {}

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getTotalDepreciation(): float
    {
        return $this->totalDepreciation;
    }

    public function hasErrors(): bool
    {
        return $this->errorCount > 0;
    }

    public function getSuccessRate(): float
    {
        $total = $this->processedCount + $this->errorCount;
        if ($total === 0) {
            return 100.0;
        }
        return ($this->processedCount / $total) * 100;
    }

    public function getDurationInSeconds(): int
    {
        return $this->runDate->diff($this->completedDate)->s;
    }

    public static function fromResult(
        DepreciationRunResult $result,
        string $tenantId,
        bool $postedToGL = false
    ): self {
        return new self(
            runId: $result->runId ?? '',
            tenantId: $tenantId,
            periodId: $result->periodId,
            processedCount: $result->successCount,
            errorCount: $result->failureCount,
            totalDepreciation: $result->totalDepreciation,
            currency: 'USD',
            runDate: $result->runDate,
            completedDate: new DateTimeImmutable(),
            postedToGL: $postedToGL
        );
    }

    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'tenant_id' => $this->tenantId,
            'period_id' => $this->periodId,
            'processed_count' => $this->processedCount,
            'error_count' => $this->errorCount,
            'total_depreciation' => $this->totalDepreciation,
            'currency' => $this->currency,
            'run_date' => $this->runDate->format('Y-m-d H:i:s'),
            'completed_date' => $this->completedDate->format('Y-m-d H:i:s'),
            'posted_to_gl' => $this->postedToGL,
            'success_rate' => $this->getSuccessRate(),
            'context' => $this->context,
        ];
    }
}
