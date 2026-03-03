<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Events;

use Nexus\EventStream\Contracts\EventInterface;

/**
 * Event fired when a vendor quote is uploaded and ready for extraction.
 */
final readonly class QuoteUploaded implements EventInterface
{
    public const EVENT_NAME = 'quotation.uploaded';
    public const EVENT_TYPE = 'IntegrationEvent';
    public const AGGREGATE_TYPE = 'rfq';
    public const VERSION = 1;

    public string $tenantId;
    public string $documentId;
    public string $rfqId;
    public string $vendorId;

    private string $eventId;
    private \DateTimeImmutable $occurredAt;

    /**
     * @param string $tenantId
     * @param string $documentId Reference to the document in Nexus\Storage
     * @param string $rfqId Associated RFQ
     * @param string $vendorId Submitting vendor
     */
    public function __construct(
        string $tenantId,
        string $documentId,
        string $rfqId,
        string $vendorId
    ) {
        $this->tenantId = trim($tenantId);
        $this->documentId = trim($documentId);
        $this->rfqId = trim($rfqId);
        $this->vendorId = trim($vendorId);

        if (empty($this->tenantId)) {
            throw new \InvalidArgumentException('tenantId cannot be empty after trimming');
        }
        if (empty($this->documentId)) {
            throw new \InvalidArgumentException('documentId cannot be empty after trimming');
        }
        if (empty($this->rfqId)) {
            throw new \InvalidArgumentException('rfqId cannot be empty after trimming');
        }
        if (empty($this->vendorId)) {
            throw new \InvalidArgumentException('vendorId cannot be empty after trimming');
        }

        $this->eventId = bin2hex(random_bytes(16));
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function getAggregateId(): string
    {
        return $this->rfqId;
    }

    public function getAggregateType(): string
    {
        return self::AGGREGATE_TYPE;
    }

    public function getVersion(): int
    {
        return self::VERSION;
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
        return $this->occurredAt;
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
        return 'tenant-' . $this->tenantId . '-rfq-' . $this->rfqId;
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
