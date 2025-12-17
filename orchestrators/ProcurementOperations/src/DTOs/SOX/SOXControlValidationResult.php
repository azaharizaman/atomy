<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use Nexus\ProcurementOperations\Enums\SOXControlType;

/**
 * Result of a single SOX control validation.
 */
final readonly class SOXControlValidationResult
{
    /**
     * @param array<string, mixed> $evidence Evidence collected during validation
     * @param array<string> $failureReasons Detailed reasons for failure
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public SOXControlPoint $controlPoint,
        public SOXControlResult $result,
        public SOXControlType $controlType,
        public int $riskLevel,
        public array $evidence = [],
        public array $failureReasons = [],
        public ?string $overrideApprover = null,
        public ?string $overrideReason = null,
        public \DateTimeImmutable $validatedAt = new \DateTimeImmutable(),
        public float $validationDurationMs = 0.0,
        public array $metadata = [],
    ) {}

    /**
     * Create a passed result.
     *
     * @param array<string, mixed> $evidence
     */
    public static function passed(
        SOXControlPoint $controlPoint,
        array $evidence = [],
        float $durationMs = 0.0,
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::PASSED,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            evidence: $evidence,
            validationDurationMs: $durationMs,
        );
    }

    /**
     * Create a failed result.
     *
     * @param array<string> $failureReasons
     * @param array<string, mixed> $evidence
     */
    public static function failed(
        SOXControlPoint $controlPoint,
        array $failureReasons,
        array $evidence = [],
        float $durationMs = 0.0,
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::FAILED,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            evidence: $evidence,
            failureReasons: $failureReasons,
            validationDurationMs: $durationMs,
        );
    }

    /**
     * Create a skipped result.
     */
    public static function skipped(
        SOXControlPoint $controlPoint,
        string $reason,
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::SKIPPED,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            metadata: ['skip_reason' => $reason],
        );
    }

    /**
     * Create an overridden result.
     *
     * @param array<string, mixed> $evidence
     */
    public static function overridden(
        SOXControlPoint $controlPoint,
        string $approver,
        string $reason,
        array $evidence = [],
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::OVERRIDDEN,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            evidence: $evidence,
            overrideApprover: $approver,
            overrideReason: $reason,
        );
    }

    /**
     * Create an error result.
     */
    public static function error(
        SOXControlPoint $controlPoint,
        string $errorMessage,
        float $durationMs = 0.0,
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::ERROR,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            failureReasons: [$errorMessage],
            validationDurationMs: $durationMs,
        );
    }

    /**
     * Create a timeout result.
     */
    public static function timeout(
        SOXControlPoint $controlPoint,
        float $durationMs,
    ): self {
        return new self(
            controlPoint: $controlPoint,
            result: SOXControlResult::TIMEOUT,
            controlType: $controlPoint->getControlType(),
            riskLevel: $controlPoint->getRiskLevel(),
            failureReasons: ['Control validation timed out'],
            validationDurationMs: $durationMs,
        );
    }

    /**
     * Check if this result allows the transaction to proceed.
     */
    public function allowsProceeding(): bool
    {
        return $this->result->allowsProceeding();
    }

    /**
     * Check if this is a high-risk failure (risk level >= 4).
     */
    public function isHighRiskFailure(): bool
    {
        return !$this->allowsProceeding() && $this->riskLevel >= 4;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'control_point' => $this->controlPoint->value,
            'control_point_description' => $this->controlPoint->description(),
            'result' => $this->result->value,
            'result_description' => $this->result->description(),
            'control_type' => $this->controlType->value,
            'risk_level' => $this->riskLevel,
            'allows_proceeding' => $this->allowsProceeding(),
            'is_high_risk_failure' => $this->isHighRiskFailure(),
            'evidence' => $this->evidence,
            'failure_reasons' => $this->failureReasons,
            'override_approver' => $this->overrideApprover,
            'override_reason' => $this->overrideReason,
            'validated_at' => $this->validatedAt->format(\DateTimeInterface::ATOM),
            'validation_duration_ms' => $this->validationDurationMs,
            'metadata' => $this->metadata,
        ];
    }
}
