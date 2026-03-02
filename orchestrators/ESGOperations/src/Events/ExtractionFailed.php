<?php

declare(strict_types=1);

namespace Nexus\ESGOperations\Events;

use Nexus\EventStream\Contracts\EventInterface;

/**
 * Event fired when an ESG extraction result has low confidence and requires manual review.
 */
final readonly class ExtractionFailed implements EventInterface
{
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $metricId,
        public float $confidence,
        public array $rawMetadata = []
    ) {
    }

    public function getEventId(): string { return bin2hex(random_bytes(16)); }
    public function getEventName(): string { return 'esg.extraction.failed'; }
    public function getEventType(): string { return 'FailureEvent'; }
    public function getAggregateId(): string { return $this->documentId; }
    public function getAggregateType(): string { return 'document'; }
    public function getVersion(): int { return 1; }
    public function getPayload(): array {
        return [
            'tenant_id' => $this->tenantId,
            'document_id' => $this->documentId,
            'metric_id' => $this->metricId,
            'confidence' => $this->confidence,
        ];
    }
    public function getMetadata(): array { return $this->rawMetadata; }
    public function getOccurredAt(): \DateTimeImmutable { return new \DateTimeImmutable(); }
    public function getCausationId(): ?string { return null; }
    public function getCorrelationId(): ?string { return null; }
    public function getStreamName(): ?string { return 'esg-failures-' . $this->tenantId; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getUserId(): ?string { return null; }
}
