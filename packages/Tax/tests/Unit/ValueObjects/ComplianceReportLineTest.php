<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Tax\ValueObjects\ComplianceReportLine;
use PHPUnit\Framework\TestCase;

final class ComplianceReportLineTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $line = new ComplianceReportLine(
            lineCode: 'box_1',
            description: 'Total sales',
            amount: '150000.00',
            taxCode: 'VAT-STANDARD',
            jurisdictionCode: 'GB',
        );

        $this->assertSame('box_1', $line->lineCode);
        $this->assertSame('Total sales', $line->description);
        $this->assertSame('150000.00', $line->amount);
        $this->assertSame('VAT-STANDARD', $line->taxCode);
        $this->assertSame('GB', $line->jurisdictionCode);
    }

    public function test_it_validates_empty_line_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Line code cannot be empty');

        new ComplianceReportLine(
            lineCode: '',
            description: 'Total sales',
            amount: '150000.00',
        );
    }

    public function test_it_validates_empty_description(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Description cannot be empty');

        new ComplianceReportLine(
            lineCode: 'box_1',
            description: '',
            amount: '150000.00',
        );
    }

    public function test_it_validates_numeric_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be numeric string');

        new ComplianceReportLine(
            lineCode: 'box_1',
            description: 'Total sales',
            amount: 'invalid',
        );
    }

    public function test_it_calculates_total_with_children(): void
    {
        $child1 = new ComplianceReportLine(
            lineCode: 'box_1a',
            description: 'Domestic sales',
            amount: '100000.00',
        );

        $child2 = new ComplianceReportLine(
            lineCode: 'box_1b',
            description: 'International sales',
            amount: '50000.00',
        );

        $parent = new ComplianceReportLine(
            lineCode: 'box_1',
            description: 'Total sales',
            amount: '150000.00',
            children: [$child1, $child2],
        );

        // Total = 150000.00 + 100000.00 + 50000.00 = 300000.00
        $total = $parent->getTotalWithChildren();
        $this->assertSame('300000.00', $total);
    }
}
