<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Listeners;

use Nexus\QuotationIntelligence\Events\QuoteUploaded;
use Psr\Log\LoggerInterface;

/**
 * Listener that reacts to QuoteUploaded event and kicks off the async processing.
 * 
 * In a real-world scenario, this would dispatch a job to a background worker
 * to perform the actual extraction and normalization.
 */
final readonly class ProcessQuoteUploadListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(QuoteUploaded $event): void
    {
        $this->logger->info('Starting intelligence pipeline for uploaded quote', [
            'document_id' => $event->documentId,
            'rfq_id' => $event->rfqId,
            'vendor_id' => $event->vendorId,
        ]);

        // Dispatch background job (implementation detail of the adapter layer)
        // For Layer 2, we just log and hand off to the workflow coordinator.
    }
}
