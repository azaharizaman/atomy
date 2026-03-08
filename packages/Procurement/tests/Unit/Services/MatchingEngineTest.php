<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\Procurement\Services\MatchingEngine;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MatchingEngineTest extends TestCase
{
    private MatchingEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new MatchingEngine($this->createMock(LoggerInterface::class));
    }

    public function test_exact_match_returns_matched(): void
    {
        $poLine = $this->createPoLine('PO-001-L001', 10.0, 25.0);
        $grnLine = $this->createGrnLine('PO-001-L001', 10.0);

        $result = $this->engine->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 10.0,
            'unit_price' => 25.0,
            'line_total' => 250.0,
        ]);

        self::assertTrue($result['matched']);
        self::assertEmpty($result['discrepancies']);
        self::assertStringContainsString('APPROVE', $result['recommendation']);
    }

    public function test_line_reference_mismatch_returns_not_matched(): void
    {
        $poLine = $this->createPoLine('PO-001-L001', 10.0, 25.0);
        $grnLine = $this->createGrnLine('PO-001-L002', 10.0);

        $result = $this->engine->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 10.0,
            'unit_price' => 25.0,
            'line_total' => 250.0,
        ]);

        self::assertFalse($result['matched']);
        self::assertArrayHasKey('line_reference', $result['discrepancies']);
    }

    public function test_quantity_variance_exceeds_tolerance_returns_discrepancy(): void
    {
        $poLine = $this->createPoLine('PO-001-L001', 100.0, 10.0);
        $grnLine = $this->createGrnLine('PO-001-L001', 100.0);

        $result = $this->engine->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 120.0,
            'unit_price' => 10.0,
            'line_total' => 1200.0,
        ]);

        self::assertFalse($result['matched']);
        self::assertArrayHasKey('quantity', $result['discrepancies']);
    }

    public function test_price_variance_exceeds_tolerance_returns_discrepancy(): void
    {
        $poLine = $this->createPoLine('PO-001-L001', 10.0, 100.0);
        $grnLine = $this->createGrnLine('PO-001-L001', 10.0);

        $result = $this->engine->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 10.0,
            'unit_price' => 120.0,
            'line_total' => 1200.0,
        ]);

        self::assertFalse($result['matched']);
        self::assertArrayHasKey('unit_price', $result['discrepancies']);
    }

    public function test_batch_match_returns_aggregated_results(): void
    {
        $poLine = $this->createPoLine('PO-001-L001', 10.0, 25.0);
        $grnLine = $this->createGrnLine('PO-001-L001', 10.0);

        $result = $this->engine->performBatchMatch([
            [
                'po_line' => $poLine,
                'grn_line' => $grnLine,
                'invoice_line' => ['quantity' => 10.0, 'unit_price' => 25.0, 'line_total' => 250.0],
            ],
        ]);

        self::assertTrue($result['overall_matched']);
        self::assertSame(1, $result['total_lines']);
        self::assertSame(1, $result['matched_lines']);
        self::assertSame(0, $result['discrepancy_lines']);
    }

    private function createPoLine(string $ref, float $qty, float $price): PurchaseOrderLineInterface
    {
        $line = $this->createMock(PurchaseOrderLineInterface::class);
        $line->method('getLineReference')->willReturn($ref);
        $line->method('getQuantity')->willReturn($qty);
        $line->method('getUnitPrice')->willReturn($price);

        return $line;
    }

    private function createGrnLine(string $poRef, float $qty): GoodsReceiptLineInterface
    {
        $line = $this->createMock(GoodsReceiptLineInterface::class);
        $line->method('getPoLineReference')->willReturn($poRef);
        $line->method('getQuantity')->willReturn($qty);

        return $line;
    }
}
