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

        $dataProvider = new DuplicateInvoiceDataProvider($this->invoiceQuery);

        $this->service = new DuplicateInvoiceDetectionService(
            $dataProvider,
            $this->eventDispatcher,
        );
    }

    public function test_returns_no_duplicates_when_none_found(): void
    {
        $request = $this->createRequest();

        $this->setupEmptyQueryResults();

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DuplicateCheckPassedEvent::class));

        $result = $this->service->checkForDuplicates($request);

        $this->assertFalse($result->hasDuplicates);
        $this->assertFalse($result->shouldBlock);
        $this->assertEmpty($result->matches);
        $this->assertEquals('none', $result->highestRiskLevel);
    }

    public function test_detects_exact_duplicate(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')
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

        $this->setupOtherQueriesEmpty();

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DuplicateInvoiceDetectedEvent::class));

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock);
        $this->assertCount(1, $result->matches);
        $this->assertEquals('critical', $result->highestRiskLevel);
        $this->assertEquals(1.0, $result->highestConfidence);
        $this->assertEquals(DuplicateMatchType::EXACT_MATCH, $result->matches[0]->matchType);
    }

    public function test_detects_invoice_number_match_with_different_amount(): void
    {
        $request = $this->createRequest();

        $this->invoiceQuery->method('findByExactInvoiceNumber')
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

        $this->setupOtherQueriesEmpty();

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock);
        $this->assertEquals(DuplicateMatchType::INVOICE_NUMBER_MATCH, $result->matches[0]->matchType);
        $this->assertEquals('high', $result->highestRiskLevel);
    }

    public function test_detects_amount_date_match(): void
    {
        $request = $this->createRequest();

        $this->setupEmptyExactQueries();

        $this->invoiceQuery->method('findByAmountAndDate')
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

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertFalse($result->shouldBlock); // Amount-date match doesn't block
        $this->assertEquals(DuplicateMatchType::AMOUNT_DATE_MATCH, $result->matches[0]->matchType);
        $this->assertEquals('medium', $result->highestRiskLevel);
    }

    public function test_strict_mode_blocks_any_match(): void
    {
        $request = new DuplicateCheckRequest(
            tenantId: 'tenant-1',
            vendorId: 'vendor-1',
            invoiceNumber: 'INV-2024-001',
            invoiceAmount: Money::of(1000.00, 'MYR'),
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            strictMode: true,
        );

        $this->setupEmptyExactQueries();

        // Only an amount-vendor match (normally wouldn't block)
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')
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

        $result = $this->service->checkForDuplicates($request);

        $this->assertTrue($result->hasDuplicates);
        $this->assertTrue($result->shouldBlock); // Blocked because strict mode
    }

    public function test_multiple_match_types_sorted_by_confidence(): void
    {
        $request = $this->createRequest();

        // Multiple match types returned
        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')
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
        $this->invoiceQuery->method('findByAmount')
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

    private function setupEmptyQueryResults(): void
    {
        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);
    }

    private function setupEmptyExactQueries(): void
    {
        $this->invoiceQuery->method('findByExactInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
    }

    private function setupOtherQueriesEmpty(): void
    {
        $this->invoiceQuery->method('findByNormalizedInvoiceNumber')->willReturn([]);
        $this->invoiceQuery->method('findByAmountAndDate')->willReturn([]);
        $this->invoiceQuery->method('findByAmount')->willReturn([]);
        $this->invoiceQuery->method('findByPOReference')->willReturn([]);
        $this->invoiceQuery->method('findByDocumentHash')->willReturn([]);
        $this->invoiceQuery->method('findByFingerprint')->willReturn([]);
    }
}
