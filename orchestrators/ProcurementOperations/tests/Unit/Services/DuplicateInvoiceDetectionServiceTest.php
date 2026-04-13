<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\InvoiceDuplicateQueryInterface;
use Nexus\ProcurementOperations\DataProviders\DuplicateInvoiceDataProvider;
use Nexus\ProcurementOperations\DTOs\DuplicateCheckRequest;
use Nexus\ProcurementOperations\Enums\DuplicateMatchType;
use Nexus\ProcurementOperations\Events\DuplicateCheckPassedEvent;
use Nexus\ProcurementOperations\Events\DuplicateInvoiceDetectedEvent;
use Nexus\ProcurementOperations\Services\DuplicateInvoiceDetectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

#[CoversClass(DuplicateInvoiceDetectionService::class)]
final class DuplicateInvoiceDetectionServiceTest extends TestCase
{
    private InvoiceDuplicateQueryInterface&MockObject $invoiceQuery;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private DuplicateInvoiceDetectionService $service;

    protected function setUp(): void
    {
        $this->invoiceQuery = $this->createMock(InvoiceDuplicateQueryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function initService(): void
    {
        $dataProvider = new DuplicateInvoiceDataProvider($this->invoiceQuery);
        $this->service = new DuplicateInvoiceDetectionService(
            $dataProvider,
            $this->eventDispatcher,
        );
    }

    #[Test]
    public function it_returns_no_duplicates_when_none_found(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->expects($this->once())->method('findByExactInvoiceNumber')->with('tenant-1', 'vendor-1', 'INV-2024-001')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByNormalizedInvoiceNumber')->with('tenant-1', 'vendor-1', 'INV2024001')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmountAndDate')->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmount')->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByPOReference')->with('tenant-1', null)->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByDocumentHash')->with('tenant-1', null)->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByFingerprint')->with('tenant-1', null)->willReturn([]);

        $this->initService();

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DuplicateCheckPassedEvent::class));

        $result = $this->service->checkForDuplicates($request);

        $this->assertFalse($result->hasDuplicates);
        $this->assertFalse($result->shouldBlock);
        $this->assertEmpty($result->matches);
        $this->assertEquals('none', $result->highestRiskLevel);
    }

    #[Test]
    public function it_detects_exact_duplicate(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->expects($this->once())->method('findByExactInvoiceNumber')
            ->with('tenant-1', 'vendor-1', 'INV-2024-001')
            ->willReturn([
                [
                    'id' => 'inv-existing-1',
                    'invoice_number' => 'INV-2024-001',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DuplicateInvoiceDetectedEvent::class));

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock);
        $this->assertCount(1, $result->matches);
        $this->assertEquals('critical', $result->highestRiskLevel);
        $this->assertEquals(DuplicateMatchType::EXACT_MATCH, $result->matches[0]->matchType);
    }

    #[Test]
    public function it_detects_invoice_number_match_with_different_amount(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->expects($this->once())->method('findByExactInvoiceNumber')
            ->with('tenant-1', 'vendor-1', 'INV-2024-001')
            ->willReturn([
                [
                    'id' => 'inv-existing-1',
                    'invoice_number' => 'INV-2024-001',
                    'amount' => 500.00, // Different amount
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock);
        $this->assertEquals(DuplicateMatchType::INVOICE_NUMBER_MATCH, $result->matches[0]->matchType);
        $this->assertEquals('high', $result->highestRiskLevel);
    }

    #[Test]
    public function it_detects_normalized_invoice_number_match(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByNormalizedInvoiceNumber')
            ->with('tenant-1', 'vendor-1', 'INV2024001')
            ->willReturn([
                [
                    'id' => 'inv-normalized-1',
                    'invoice_number' => 'INV2024001', // Normalized format
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertEquals(DuplicateMatchType::FUZZY_INVOICE_NUMBER, $result->matches[0]->matchType);
        $this->assertArrayHasKey('original_number', $result->matches[0]->matchDetails);
    }

    #[Test]
    public function it_detects_amount_date_match(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmountAndDate')
            ->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                [
                    'id' => 'inv-existing-1',
                    'invoice_number' => 'INV-2024-999',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-14',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertFalse($result->shouldBlock); // Amount-date match doesn't block
        $this->assertEquals(DuplicateMatchType::AMOUNT_DATE_MATCH, $result->matches[0]->matchType);
        $this->assertEquals('medium', $result->highestRiskLevel);
    }

    #[Test]
    public function it_detects_amount_vendor_match(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmount')
            ->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                [
                    'id' => 'inv-existing-1',
                    'invoice_number' => 'INV-OLD',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2023-12-01',
                    'status' => 'paid',
                ],
            ]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertEquals(DuplicateMatchType::AMOUNT_VENDOR_MATCH, $result->matches[0]->matchType);
    }

    #[Test]
    public function it_detects_po_reference_match(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            poNumber: 'PO-12345',
        );

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByPOReference')
            ->with('tenant-1', 'PO-12345')
            ->willReturn([
                [
                    'id' => 'inv-po-1',
                    'invoice_number' => 'INV-OTHER',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                    'po_number' => 'PO-12345',
                ],
            ]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertEquals(DuplicateMatchType::PO_REFERENCE_MATCH, $result->matches[0]->matchType);
        $this->assertEquals('PO-12345', $result->matches[0]->matchDetails['po_number']);
    }

    #[Test]
    public function it_detects_hash_collision(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            documentHash: 'some-hash',
        );

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByDocumentHash')
            ->with('tenant-1', 'some-hash')
            ->willReturn([
                [
                    'id' => 'inv-hash-1',
                    'invoice_number' => 'INV-DIFF',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertEquals(DuplicateMatchType::HASH_COLLISION, $result->matches[0]->matchType);
    }

    #[Test]
    public function it_detects_fingerprint_match(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByFingerprint')
            ->with('tenant-1', null)
            ->willReturn([
                [
                    'id' => 'inv-fp-1',
                    'invoice_number' => 'INV-DIFF-2',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-10',
                    'status' => 'approved',
                ],
            ]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertEquals(DuplicateMatchType::HASH_COLLISION, $result->matches[0]->matchType);
        $this->assertEquals('fingerprint', $result->matches[0]->matchDetails['match_source']);
    }

    #[Test]
    public function it_strict_mode_blocks_any_match(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            strictMode: true,
        );

        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmount')
            ->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                [
                    'id' => 'inv-existing-1',
                    'invoice_number' => 'INV-2024-OTHER',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-01',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock); // Blocked because strict mode
    }

    #[Test]
    public function it_multiple_match_types_sorted_by_confidence(): void
    {
        $request = $this->createRequest();

        // Multiple match types returned
        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->expects($this->once())->method('findByAmountAndDate')
            ->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                [
                    'id' => 'inv-1',
                    'invoice_number' => 'INV-A',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-14',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->expects($this->once())->method('findByAmount')
            ->with('tenant-1', 'vendor-1', 1000.0, 'MYR', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                [
                    'id' => 'inv-2',
                    'invoice_number' => 'INV-B',
                    'amount' => 1000.00,
                    'currency' => 'MYR',
                    'date' => '2024-01-01',
                    'status' => 'approved',
                ],
            ]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);

        $this->initService();

        $result = $this->service->checkForDuplicates($request);

        $this->assertCount(2, $result->matches);
        // Amount-date has higher confidence than amount-vendor
        $this->assertEquals(DuplicateMatchType::AMOUNT_DATE_MATCH, $result->matches[0]->matchType);
        $this->assertEquals(DuplicateMatchType::AMOUNT_VENDOR_MATCH, $result->matches[1]->matchType);
    }

    private function createRequest(): DuplicateCheckRequest
    {
        return new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
        );
    }
}
