<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;

/**
 * Event dispatched when a SOX control validation fails.
 */
final readonly class SOXControlFailedEvent
{
    /**
     * @param array<string> $failureReasons
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public SOXControlPoint $controlPoint,
        public SOXControlResult $result,
        public string $entityType,
        public string $entityId,
        public string $userId,
        public array $failureReasons,
        public int $riskLevel,
        public float $validationDurationMs,
        public array $context,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from validation failure.
     *
     * @param array<string> $failureReasons
     * @param array<string, mixed> $context
     */
    public static function create(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        string $userId,
        array $failureReasons,
        float $validationDurationMs,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            result: SOXControlResult::FAILED,
            entityType: $entityType,
            entityId: $entityId,
            userId: $userId,
            failureReasons: $failureReasons,
            riskLevel: $controlPoint->getRiskLevel(),
            validationDurationMs: $validationDurationMs,
            context: $context,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get event name for dispatch.
     */
    public static function eventName(): string
    {
        return 'procurement.sox.control_failed';
    }

    /**
     * Check if this is a high-risk failure.
     */
    public function isHighRisk(): bool
    {
        return $this->riskLevel >= 4;
    }

    /**
     * Get a summary for logging.
     */
    public function getSummary(): string
    {
        return sprintf(
            'SOX Control [%s] failed for %s:%s by user %s. Risk Level: %d. Reasons: %s',
            $this->controlPoint->value,
            $this->entityType,
            $this->entityId,
            $this->userId,
            $this->riskLevel,
            implode('; ', $this->failureReasons),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'control_point' => $this->controlPoint->value,
            'result' => $this->result->value,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'user_id' => $this->userId,
            'failure_reasons' => $this->failureReasons,
            'risk_level' => $this->riskLevel,
            'validation_duration_ms' => $this->validationDurationMs,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
