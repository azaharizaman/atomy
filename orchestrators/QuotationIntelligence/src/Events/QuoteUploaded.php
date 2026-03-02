<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Events;

use Nexus\EventStream\Contracts\EventInterface;

/**
 * Event fired when a vendor quote is uploaded and ready for extraction.
 */
final readonly class QuoteUploaded implements EventInterface
{
    /**
     * @param string $tenantId
     * @param string $documentId Reference to the document in Nexus\Storage
     * @param string $rfqId Associated RFQ
     * @param string $vendorId Submitting vendor
     */
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $rfqId,
        public string $vendorId
    ) {
    }

    public function getEventId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function getEventName(): string
    {
        return 'quotation.uploaded';
    }

    public function getEventType(): string
    {
        return 'IntegrationEvent';
    }

    public function getAggregateId(): string
    {
        return $this->rfqId;
    }

    public function getAggregateType(): string
    {
        return 'rfq';
    }

    public function getVersion(): int
    {
        return 1;
    }

    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'document_id' => $this->documentId,
            'rfq_id' => $this->rfqId,
            'vendor_id' => $this->vendorId,
        ];
    }

    public function getMetadata(): array
    {
        return [];
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function getCausationId(): ?string
    {
        return null;
    }

    public function getCorrelationId(): ?string
    {
        return null;
    }

    public function getStreamName(): ?string
    {
        return 'rfq-' . $this->rfqId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserId(): ?string
    {
        return null;
    }
}
