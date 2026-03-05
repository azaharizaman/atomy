<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Listeners;

use Nexus\QuotationIntelligence\Events\QuoteUploaded;
use Nexus\QuotationIntelligence\Listeners\ProcessQuoteUploadListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProcessQuoteUploadListenerTest extends TestCase
{
    public function test_handle_logs_pipeline_start(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Starting intelligence pipeline for uploaded quote',
                $this->callback(static function (array $context): bool {
                    return $context['document_id'] === 'doc-1'
                        && $context['rfq_id'] === 'rfq-1'
                        && $context['vendor_id'] === 'vendor-1';
                })
            );

        $listener = new ProcessQuoteUploadListener($logger);
        $event = new QuoteUploaded('tenant-1', 'doc-1', 'rfq-1', 'vendor-1');

        $listener->handle($event);
    }
}

