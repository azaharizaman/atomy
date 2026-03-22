<?php

declare(strict_types=1);

namespace Nexus\Outbox\Domain;

use DateTimeImmutable;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\TenantId;

/**
 * @phpstan-type PayloadArray array<string, mixed>
 */
final readonly class OutboxEnqueueCommand
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public TenantId $tenantId,
        public DedupKey $dedupKey,
        public EventTypeRef $eventType,
        public array $payload,
        public array $metadata,
        public ?string $correlationId,
        public ?string $causationId,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
