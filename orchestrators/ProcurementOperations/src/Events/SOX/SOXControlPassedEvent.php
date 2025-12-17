<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;

/**
 * Event dispatched when a SOX control validation passes.
 */
final readonly class SOXControlPassedEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public SOXControlPoint $controlPoint,
        public string $entityType,
        public string $entityId,
        public string $userId,
        public float $validationDurationMs,
        public array $context,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from successful validation.
     *
     * @param array<string, mixed> $context
     */
    public static function create(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $entityType,
        string $entityId,
        string $userId,
        float $validationDurationMs,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            entityType: $entityType,
            entityId: $entityId,
            userId: $userId,
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
        return 'procurement.sox.control_passed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'control_point' => $this->controlPoint->value,
            'result' => SOXControlResult::PASSED->value,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'user_id' => $this->userId,
            'validation_duration_ms' => $this->validationDurationMs,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
