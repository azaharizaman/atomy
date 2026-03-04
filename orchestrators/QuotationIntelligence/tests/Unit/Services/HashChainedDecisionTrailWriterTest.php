<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use Nexus\QuotationIntelligence\Services\HashChainedDecisionTrailWriter;
use PHPUnit\Framework\TestCase;

final class HashChainedDecisionTrailWriterTest extends TestCase
{
    public function test_writes_hash_chained_entries(): void
    {
        $writer = new HashChainedDecisionTrailWriter();

        $trail = $writer->write('tenant-1', 'rfq-1', [
            ['event_type' => 'step_one', 'payload' => ['a' => 1]],
            ['event_type' => 'step_two', 'payload' => ['b' => 2]],
        ]);

        $this->assertCount(2, $trail);
        $this->assertSame(1, $trail[0]['sequence']);
        $this->assertSame(str_repeat('0', 64), $trail[0]['previous_hash']);
        $this->assertSame($trail[0]['entry_hash'], $trail[1]['previous_hash']);
        $this->assertNotSame($trail[0]['entry_hash'], $trail[1]['entry_hash']);
    }
}

