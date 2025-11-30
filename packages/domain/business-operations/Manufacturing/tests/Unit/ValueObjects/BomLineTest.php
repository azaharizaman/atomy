<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\ValueObjects;

use Nexus\Manufacturing\ValueObjects\BomLine;
use Nexus\Manufacturing\Tests\TestCase;

final class BomLineTest extends TestCase
{
    public function testCreateBomLine(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 2.5,
            uomCode: 'EA',
            lineNumber: 10,
            operationNumber: '010',
            scrapPercentage: 5.0,
            isPhantom: false,
            position: 'A1',
            notes: 'Test component',
        );

        $this->assertSame('comp-001', $line->productId);
        $this->assertSame(2.5, $line->quantity);
        $this->assertSame('EA', $line->uomCode);
        $this->assertSame(10, $line->lineNumber);
        $this->assertSame('010', $line->operationNumber);
        $this->assertSame(5.0, $line->scrapPercentage);
        $this->assertFalse($line->isPhantom);
        $this->assertSame('A1', $line->position);
        $this->assertSame('Test component', $line->notes);
    }

    public function testGetQuantityWithScrap(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 10.0,
            uomCode: 'EA',
            scrapPercentage: 10.0,
        );

        // Gross = 10 * (1 + 0.10) = 11.0
        $grossQty = $line->getQuantityWithScrap();
        $this->assertSame(11.0, $grossQty);
    }

    public function testGetQuantityWithScrapZero(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 10.0,
            uomCode: 'EA',
            scrapPercentage: 0.0,
        );

        $grossQty = $line->getQuantityWithScrap();
        $this->assertSame(10.0, $grossQty);
    }

    public function testIsPhantomBom(): void
    {
        $phantomLine = new BomLine(
            productId: 'phantom-001',
            quantity: 1.0,
            uomCode: 'EA',
            isPhantom: true,
        );

        $normalLine = new BomLine(
            productId: 'comp-001',
            quantity: 1.0,
            uomCode: 'EA',
            isPhantom: false,
        );

        $this->assertTrue($phantomLine->isPhantom);
        $this->assertFalse($normalLine->isPhantom);
    }

    public function testIsEffectiveAt(): void
    {
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');

        $line = new BomLine(
            productId: 'comp-001',
            quantity: 1.0,
            uomCode: 'EA',
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
        );

        $this->assertTrue($line->isEffectiveAt(new \DateTimeImmutable('2024-06-15')));
        $this->assertFalse($line->isEffectiveAt(new \DateTimeImmutable('2023-12-31')));
        $this->assertFalse($line->isEffectiveAt(new \DateTimeImmutable('2025-01-01')));
    }

    public function testWithQuantity(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 2.0,
            uomCode: 'EA',
        );

        $newLine = $line->withQuantity(5.0);

        $this->assertSame(5.0, $newLine->quantity);
        $this->assertSame('comp-001', $newLine->productId);
        $this->assertSame('EA', $newLine->uomCode);
    }

    public function testWithEffectivity(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 1.0,
            uomCode: 'EA',
        );

        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');

        $newLine = $line->withEffectivity($effectiveFrom, $effectiveTo);

        $this->assertSame($effectiveFrom, $newLine->effectiveFrom);
        $this->assertSame($effectiveTo, $newLine->effectiveTo);
    }

    public function testToArray(): void
    {
        $line = new BomLine(
            productId: 'comp-001',
            quantity: 2.5,
            uomCode: 'EA',
            lineNumber: 10,
            scrapPercentage: 5.0,
            isPhantom: false,
            position: 'A1',
            notes: 'Test notes',
        );

        $array = $line->toArray();

        $this->assertSame('comp-001', $array['productId']);
        $this->assertSame(2.5, $array['quantity']);
        $this->assertSame('EA', $array['uomCode']);
        $this->assertSame(10, $array['lineNumber']);
        $this->assertSame(5.0, $array['scrapPercentage']);
        $this->assertFalse($array['isPhantom']);
        $this->assertSame('A1', $array['position']);
        $this->assertSame('Test notes', $array['notes']);
    }

    public function testFromArray(): void
    {
        $data = [
            'productId' => 'comp-001',
            'quantity' => 2.5,
            'uomCode' => 'EA',
            'lineNumber' => 10,
            'scrapPercentage' => 5.0,
            'isPhantom' => true,
            'position' => 'B2',
            'notes' => 'From array',
        ];

        $line = BomLine::fromArray($data);

        $this->assertSame('comp-001', $line->productId);
        $this->assertSame(2.5, $line->quantity);
        $this->assertSame('EA', $line->uomCode);
        $this->assertSame(10, $line->lineNumber);
        $this->assertSame(5.0, $line->scrapPercentage);
        $this->assertTrue($line->isPhantom);
        $this->assertSame('B2', $line->position);
        $this->assertSame('From array', $line->notes);
    }

    public function testThrowsExceptionForInvalidQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        new BomLine(
            productId: 'comp-001',
            quantity: 0.0,
            uomCode: 'EA',
        );
    }

    public function testThrowsExceptionForInvalidScrapPercentage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scrap percentage must be between 0 and 100');

        new BomLine(
            productId: 'comp-001',
            quantity: 1.0,
            uomCode: 'EA',
            scrapPercentage: 150.0,
        );
    }
}
