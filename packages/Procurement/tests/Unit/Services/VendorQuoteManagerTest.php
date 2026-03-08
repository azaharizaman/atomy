<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
use Nexus\Procurement\Services\VendorQuoteManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class VendorQuoteManagerTest extends TestCase
{
    private VendorQuoteRepositoryInterface $repository;
    private VendorQuoteManager $manager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VendorQuoteRepositoryInterface::class);
        $this->manager = new VendorQuoteManager($this->repository, $this->createMock(LoggerInterface::class));
    }

    public function test_create_quote_delegates_to_repository(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');

        $this->repository->expects(self::once())
            ->method('create')
            ->with('tenant-1', 'req-1', self::arrayHasKey('rfq_number'))
            ->willReturn($quote);

        $result = $this->manager->createQuote('tenant-1', 'req-1', [
            'rfq_number' => 'RFQ-001',
            'vendor_id' => 'vendor-1',
            'quote_reference' => 'VQ-001',
            'quoted_date' => '2025-01-01',
            'valid_until' => '2025-02-01',
            'lines' => [
                ['item_code' => 'A', 'description' => 'Item A', 'quantity' => 1, 'unit' => 'EA', 'unit_price' => 10],
            ],
        ]);

        self::assertSame($quote, $result);
    }

    public function test_accept_quote_delegates_to_repository(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');
        $accepted = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'accepted');

        $this->repository->method('findById')->with('quote-1')->willReturn($quote);
        $this->repository->expects(self::once())
            ->method('accept')
            ->with('quote-1', 'user-1')
            ->willReturn($accepted);

        $result = $this->manager->acceptQuote('quote-1', 'user-1');

        self::assertSame($accepted, $result);
    }

    public function test_accept_quote_throws_when_not_found(): void
    {
        $this->repository->method('findById')->with('quote-x')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        $this->manager->acceptQuote('quote-x', 'user-1');
    }

    public function test_reject_quote_delegates_to_repository(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');
        $rejected = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'rejected');

        $this->repository->method('findById')->with('quote-1')->willReturn($quote);
        $this->repository->expects(self::once())
            ->method('reject')
            ->with('quote-1', 'Price too high')
            ->willReturn($rejected);

        $result = $this->manager->rejectQuote('quote-1', 'Price too high');

        self::assertSame($rejected, $result);
    }

    public function test_get_quote_delegates_to_repository(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');

        $this->repository->expects(self::once())
            ->method('findById')
            ->with('quote-1')
            ->willReturn($quote);

        $result = $this->manager->getQuote('quote-1');

        self::assertSame($quote, $result);
    }

    public function test_get_quotes_for_requisition_delegates(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');

        $this->repository->expects(self::once())
            ->method('findByRequisitionId')
            ->with('req-1')
            ->willReturn([$quote]);

        $result = $this->manager->getQuotesForRequisition('req-1');

        self::assertSame([$quote], $result);
    }

    public function test_get_quotes_by_vendor_delegates(): void
    {
        $quote = $this->createMockQuote('quote-1', 'RFQ-001', 'vendor-1', 'pending');

        $this->repository->expects(self::once())
            ->method('findByVendorId')
            ->with('tenant-1', 'vendor-1')
            ->willReturn([$quote]);

        $result = $this->manager->getQuotesByVendor('tenant-1', 'vendor-1');

        self::assertSame([$quote], $result);
    }

    public function test_compare_quotes_returns_matrix_with_recommendation(): void
    {
        $quote = $this->createMockQuoteWithLines('quote-1', 'vendor-1', 100, [['qty' => 10, 'price' => 10]]);

        $this->repository->method('findByRequisitionId')->with('req-1')->willReturn([$quote]);

        $result = $this->manager->compareQuotes('req-1');

        self::assertSame('req-1', $result['requisition_id']);
        self::assertSame(1, $result['quote_count']);
        self::assertNotEmpty($result['quotes']);
        self::assertArrayHasKey('recommendation', $result);
    }

    private function createMockQuote(string $id, string $rfqNumber, string $vendorId, string $status): VendorQuoteInterface
    {
        $q = $this->createMock(VendorQuoteInterface::class);
        $q->method('getId')->willReturn($id);
        $q->method('getRfqNumber')->willReturn($rfqNumber);
        $q->method('getVendorId')->willReturn($vendorId);
        $q->method('getStatus')->willReturn($status);

        return $q;
    }

    private function createMockQuoteWithLines(string $id, string $vendorId, float $total, array $lines): VendorQuoteInterface
    {
        $lineData = [];
        foreach ($lines as $l) {
            $qty = $l['qty'] ?? 1;
            $price = $l['price'] ?? 10;
            $lineData[] = ['quantity' => $qty, 'unit_price' => $price, 'lead_time_days' => 7];
        }

        $q = $this->createMock(VendorQuoteInterface::class);
        $q->method('getId')->willReturn($id);
        $q->method('getRfqNumber')->willReturn('RFQ-001');
        $q->method('getVendorId')->willReturn($vendorId);
        $q->method('getStatus')->willReturn('pending');
        $q->method('getLines')->willReturn($lineData);
        $q->method('getPaymentTerms')->willReturn('Net 30');

        return $q;
    }
}
