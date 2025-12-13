<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;

/**
 * Aggregate result of all SOX control validations for a transaction.
 */
final readonly class SOXControlValidationResponse
{
    /**
     * @param array<SOXControlValidationResult> $controlResults Individual control results
     * @param array<string, mixed> $metadata Additional response metadata
     */
    public function __construct(
        public string $transactionId,
        public string $transactionType,
        public bool $passed,
        public bool $allowsProceeding,
        public int $totalControls,
        public int $passedControls,
        public int $failedControls,
        public int $skippedControls,
        public int $overriddenControls,
        public array $controlResults,
        public float $totalValidationTimeMs,
        public \DateTimeImmutable $validatedAt,
        public array $metadata = [],
    ) {}

    /**
     * Create a response from individual control results.
     *
     * @param array<SOXControlValidationResult> $controlResults
     */
    public static function fromResults(
        string $transactionId,
        string $transactionType,
        array $controlResults,
        float $totalTimeMs,
    ): self {
        $passedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $overriddenCount = 0;
        $allowsProceeding = true;

        foreach ($controlResults as $result) {
            match ($result->result) {
                SOXControlResult::PASSED => $passedCount++,
                SOXControlResult::FAILED, SOXControlResult::ERROR, SOXControlResult::TIMEOUT => $failedCount++,
                SOXControlResult::SKIPPED => $skippedCount++,
                SOXControlResult::OVERRIDDEN => $overriddenCount++,
                SOXControlResult::PENDING_REVIEW => null,
            };

            if (!$result->allowsProceeding()) {
                $allowsProceeding = false;
            }
        }

        return new self(
            transactionId: $transactionId,
            transactionType: $transactionType,
            passed: $failedCount === 0,
            allowsProceeding: $allowsProceeding,
            totalControls: count($controlResults),
            passedControls: $passedCount,
            failedControls: $failedCount,
            skippedControls: $skippedCount,
            overriddenControls: $overriddenCount,
            controlResults: $controlResults,
            totalValidationTimeMs: $totalTimeMs,
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get all failed control results.
     *
     * @return array<SOXControlValidationResult>
     */
    public function getFailedResults(): array
    {
        return array_filter(
            $this->controlResults,
            fn (SOXControlValidationResult $r) => !$r->allowsProceeding()
        );
    }

    /**
     * Get high-risk failures (risk level >= 4).
     *
     * @return array<SOXControlValidationResult>
     */
    public function getHighRiskFailures(): array
    {
        return array_filter(
            $this->controlResults,
            fn (SOXControlValidationResult $r) => $r->isHighRiskFailure()
        );
    }

    /**
     * Get result for a specific control point.
     */
    public function getControlResult(SOXControlPoint $controlPoint): ?SOXControlValidationResult
    {
        foreach ($this->controlResults as $result) {
            if ($result->controlPoint === $controlPoint) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Check if any controls require override approval.
     */
    public function requiresOverrideApproval(): bool
    {
        return $this->failedControls > 0 && !$this->allowsProceeding;
    }

    /**
     * Get the highest risk level among failed controls.
     */
    public function getHighestFailedRiskLevel(): int
    {
        $maxRisk = 0;
        foreach ($this->getFailedResults() as $result) {
            $maxRisk = max($maxRisk, $result->riskLevel);
        }

        return $maxRisk;
    }

    /**
     * Get a summary of failure reasons.
     *
     * @return array<string>
     */
    public function getFailureReasonsSummary(): array
    {
        $reasons = [];
        foreach ($this->getFailedResults() as $result) {
            $controlDesc = $result->controlPoint->description();
            foreach ($result->failureReasons as $reason) {
                $reasons[] = "{$controlDesc}: {$reason}";
            }
        }

        return $reasons;
    }

    /**
     * Get the success rate as a percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->totalControls === 0) {
            return 100.0;
        }

        $successCount = $this->passedControls + $this->skippedControls + $this->overriddenControls;

        return round(($successCount / $this->totalControls) * 100, 2);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'transaction_type' => $this->transactionType,
            'passed' => $this->passed,
            'allows_proceeding' => $this->allowsProceeding,
            'total_controls' => $this->totalControls,
            'passed_controls' => $this->passedControls,
            'failed_controls' => $this->failedControls,
            'skipped_controls' => $this->skippedControls,
            'overridden_controls' => $this->overriddenControls,
            'success_rate' => $this->getSuccessRate(),
            'highest_failed_risk_level' => $this->getHighestFailedRiskLevel(),
            'requires_override_approval' => $this->requiresOverrideApproval(),
            'control_results' => array_map(
                fn (SOXControlValidationResult $r) => $r->toArray(),
                $this->controlResults
            ),
            'failure_reasons_summary' => $this->getFailureReasonsSummary(),
            'total_validation_time_ms' => $this->totalValidationTimeMs,
            'validated_at' => $this->validatedAt->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }
}
