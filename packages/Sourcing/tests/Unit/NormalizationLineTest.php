<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\ValueObjects\NormalizationLine;
use PHPUnit\Framework\TestCase;

final class NormalizationLineTest extends TestCase
{
    public function test_holds_line_fields(): void
    {
        $line = new NormalizationLine(
            'ln-1',
            'Widget',
            10.0,
            'EA',
            2.5,
            'rfq-99',
        );

        $this->assertSame('ln-1', $line->id);
        $this->assertSame('Widget', $line->description);
        $this->assertSame(10.0, $line->quantity);
        $this->assertSame('EA', $line->uom);
        $this->assertSame(2.5, $line->unitPrice);
        $this->assertSame('rfq-99', $line->rfqLineId);
    }

    public function test_optional_rfq_line_id_defaults_to_null(): void
    {
        $line = new NormalizationLine('a', 'b', 1.0, 'U', 0.0);

        $this->assertNull($line->rfqLineId);
    }
}
