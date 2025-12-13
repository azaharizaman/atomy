<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

/**
 * Event dispatched when SOX control validation times out.
 */
final readonly class SOXControlTimeoutEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public string $controlPoint,
        public string $entityType,
        public string $entityId,
        public float $timeoutDurationMs,
        public float $configuredTimeoutMs,
        public string $userId,
        public \DateTimeImmutable $occurredAt,
        public array $context,
    ) {}

    /**
     * Create timeout event.
     *
     * @param array<string, mixed> $context
     */
    public static function create(
        string $tenantId,
        string $controlPoint,
        string $entityType,
        string $entityId,
        float $timeoutDurationMs,
        float $configuredTimeoutMs,
        string $userId,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            entityType: $entityType,
            entityId: $entityId,
            timeoutDurationMs: $timeoutDurationMs,
            configuredTimeoutMs: $configuredTimeoutMs,
            userId: $userId,
            occurredAt: new \DateTimeImmutable(),
            context: $context,
        );
    }

    /**
     * Get event name for dispatch.
     */
    public static function eventName(): string
    {
        return 'procurement.sox.control_timeout';
    }

    /**
     * Get how much the timeout was exceeded by.
     */
    public function getTimeoutExceededBy(): float
    {
        return $this->timeoutDurationMs - $this->configuredTimeoutMs;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'control_point' => $this->controlPoint,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'timeout_duration_ms' => $this->timeoutDurationMs,
            'configured_timeout_ms' => $this->configuredTimeoutMs,
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
            'context' => $this->context,
        ];
    }
}
