<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\ValueObjects;

use DateTimeImmutable;
use Nexus\FeatureFlags\Contracts\FlagAuditRecordInterface;
use Nexus\FeatureFlags\Enums\AuditAction;

/**
 * Immutable value object representing a feature flag audit record.
 *
 * Implements FlagAuditRecordInterface for returning audit history.
 */
final readonly class FlagAuditRecord implements FlagAuditRecordInterface
{
    /**
     * @param string $id Unique identifier (ULID/UUID)
     * @param string $flagName Name of the flag
     * @param AuditAction $action Type of change
     * @param string|null $userId User who made the change
     * @param string|null $tenantId Tenant scope
     * @param array<string, mixed>|null $before State before change
     * @param array<string, mixed>|null $after State after change
     * @param string|null $reason Reason for the change
     * @param array<string, mixed> $metadata Additional metadata
     * @param DateTimeImmutable $occurredAt When the change occurred
     * @param int|null $sequence Event sequence number (for event sourcing)
     */
    public function __construct(
        private string $id,
        private string $flagName,
        private AuditAction $action,
        private ?string $userId,
        private ?string $tenantId,
        private ?array $before,
        private ?array $after,
        private ?string $reason,
        private array $metadata,
        private DateTimeImmutable $occurredAt,
        private ?int $sequence = null
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFlagName(): string
    {
        return $this->flagName;
    }

    public function getAction(): AuditAction
    {
        return $this->action;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getBefore(): ?array
    {
        // Defensive copy to preserve immutability
        return $this->before !== null ? [...$this->before] : null;
    }

    public function getAfter(): ?array
    {
        // Defensive copy to preserve immutability
        return $this->after !== null ? [...$this->after] : null;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getMetadata(): array
    {
        // Defensive copy to preserve immutability
        return [...$this->metadata];
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function isCritical(): bool
    {
        return $this->action->isCritical();
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    /**
     * Create from array data (typically from database/event store).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            flagName: $data['flag_name'],
            action: AuditAction::from($data['action']),
            userId: $data['user_id'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            before: $data['before'] ?? null,
            after: $data['after'] ?? null,
            reason: $data['reason'] ?? null,
            metadata: $data['metadata'] ?? [],
            occurredAt: $data['occurred_at'] instanceof DateTimeImmutable
                ? $data['occurred_at']
                : new DateTimeImmutable($data['occurred_at']),
            sequence: $data['sequence'] ?? null
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'flag_name' => $this->flagName,
            'action' => $this->action->value,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'before' => $this->before !== null ? [...$this->before] : null,
            'after' => $this->after !== null ? [...$this->after] : null,
            'reason' => $this->reason,
            'metadata' => [...$this->metadata],
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'sequence' => $this->sequence,
            'is_critical' => $this->isCritical(),
        ];
    }
}
