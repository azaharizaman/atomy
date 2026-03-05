<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\QuoteIngestionServiceInterface;
use Nexus\QuotationIntelligence\Events\QuoteUploaded;
use Nexus\QuotationIntelligence\Exceptions\InvalidQuoteFileException;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\EventStream\Contracts\EventPublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for ingestion of unstructured vendor quote files.
 */
final readonly class QuoteIngestionService implements QuoteIngestionServiceInterface
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private EventPublisherInterface $eventPublisher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function ingest(
        string $tenantId,
        string $rfqId,
        string $vendorId,
        string $tempFilePath,
        string $originalFilename
    ): string {
        $mimeType = mime_content_type($tempFilePath) ?: 'application/octet-stream';

        if (!$this->isValidFormat($mimeType)) {
            throw new InvalidQuoteFileException("Invalid file format: {$mimeType}. Allowed: PDF, XLS, XLSX.");
        }

        $this->logger->info('Ingesting vendor quote', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'vendor_id' => $vendorId,
            'filename' => $originalFilename,
        ]);

        // 1. Persist document in Nexus\Storage via Document package
        // Note: We use a simplified create call assuming repository handles the storage mapping
        $document = $this->documentRepository->create([
            'tenant_id' => $tenantId,
            'owner_id' => $vendorId, // Vendor owns their quote
            'type' => 'vendor_quote',
            'temp_path' => $tempFilePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'metadata' => [
                'rfq_id' => $rfqId,
                'vendor_id' => $vendorId,
                'status' => 'processing',
            ],
        ]);

        $documentId = $document->getId();

        // 2. Dispatch event for async extraction pipeline
        $event = new QuoteUploaded(
            tenantId: $tenantId,
            documentId: $documentId,
            rfqId: $rfqId,
            vendorId: $vendorId
        );
        $this->eventPublisher->publish($event, $event->getStreamName());

        $this->logger->info('Vendor quote ingested and event dispatched', [
            'document_id' => $documentId,
            'rfq_id' => $rfqId,
        ]);

        return $documentId;
    }

    /**
     * @inheritDoc
     */
    public function isValidFormat(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_MIME_TYPES, true);
    }
}
