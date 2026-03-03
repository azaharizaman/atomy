<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Services\QuoteIngestionService;
use Nexus\QuotationIntelligence\Exceptions\InvalidQuoteFileException;
use Nexus\QuotationIntelligence\Events\QuoteUploaded;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\EventStream\Contracts\EventPublisherInterface;
use Psr\Log\LoggerInterface;

final class QuoteIngestionServiceTest extends TestCase
{
    private $documentRepo;
    private $publisher;
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->documentRepo = $this->createMock(DocumentRepositoryInterface::class);
        $this->publisher = $this->createMock(EventPublisherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new QuoteIngestionService(
            $this->documentRepo,
            $this->publisher,
            $this->logger
        );
    }

    public function test_ingest_successful_for_pdf(): void
    {
        // 1. Arrange
        $tempFile = tempnam(sys_get_temp_dir(), 'quote');
        file_put_contents($tempFile, '%PDF-1.4 TEST'); // Simple PDF signature
        
        $document = $this->createMock(DocumentInterface::class);
        $document->method('getId')->willReturn('DOC123');

        $this->documentRepo->expects($this->once())
            ->method('create')
            ->willReturn($document);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(QuoteUploaded::class));

        // 2. Act
        $result = $this->service->ingest(
            'tenant-1',
            'rfq-1',
            'vendor-1',
            $tempFile,
            'my_quote.pdf'
        );

        // 3. Assert
        $this->assertSame('DOC123', $result);
        unlink($tempFile);
    }

    public function test_ingest_throws_exception_for_invalid_mime_type(): void
    {
        // 1. Arrange
        $tempFile = tempnam(sys_get_temp_dir(), 'invalid');
        file_put_contents($tempFile, 'EXE_SIGNATURE_HERE');

        // 2. Act & Assert
        $this->expectException(InvalidQuoteFileException::class);
        
        try {
            $this->service->ingest(
                'tenant-1',
                'rfq-1',
                'vendor-1',
                $tempFile,
                'virus.exe'
            );
        } finally {
            unlink($tempFile);
        }
    }
}
