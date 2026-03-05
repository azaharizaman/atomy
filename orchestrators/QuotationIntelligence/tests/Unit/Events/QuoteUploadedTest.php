<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Events;

use Nexus\QuotationIntelligence\Events\QuoteUploaded;
use PHPUnit\Framework\TestCase;

final class QuoteUploadedTest extends TestCase
{
    public function test_exposes_expected_event_metadata(): void
    {
        $event = new QuoteUploaded('tenant-1', 'doc-1', 'rfq-1', 'vendor-1');

        $this->assertSame('quotation.uploaded', $event->getEventName());
        $this->assertSame('IntegrationEvent', $event->getEventType());
        $this->assertSame('rfq-1', $event->getAggregateId());
        $this->assertSame('rfq', $event->getAggregateType());
        $this->assertSame(1, $event->getVersion());
        $this->assertSame('tenant-tenant-1-rfq-rfq-1', $event->getStreamName());
        $this->assertSame('tenant-1', $event->getTenantId());
        $this->assertNull($event->getUserId());
        $this->assertNull($event->getCausationId());
        $this->assertNull($event->getCorrelationId());
        $this->assertNotEmpty($event->getEventId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());

        $this->assertSame([
            'tenant_id' => 'tenant-1',
            'document_id' => 'doc-1',
            'rfq_id' => 'rfq-1',
            'vendor_id' => 'vendor-1',
        ], $event->getPayload());
        $this->assertSame([], $event->getMetadata());
    }

    public function test_throws_when_required_fields_are_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QuoteUploaded('tenant-1', 'doc-1', 'rfq-1', '   ');
    }

    public function test_throws_when_tenant_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QuoteUploaded('   ', 'doc-1', 'rfq-1', 'vendor-1');
    }

    public function test_throws_when_document_id_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QuoteUploaded('tenant-1', '   ', 'rfq-1', 'vendor-1');
    }

    public function test_throws_when_rfq_id_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QuoteUploaded('tenant-1', 'doc-1', '   ', 'vendor-1');
    }
}
