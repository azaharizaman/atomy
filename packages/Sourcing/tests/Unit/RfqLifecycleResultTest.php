<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\ValueObjects\RfqLifecycleResult;
use PHPUnit\Framework\TestCase;

final class RfqLifecycleResultTest extends TestCase
{
    public function test_result_carries_created_and_updated_identifiers_and_counts(): void
    {
        $result = new RfqLifecycleResult(
            createdRfqId: 'rfq-123',
            updatedRfqId: 'rfq-456',
            affectedCount: 3,
            copiedLineItemCount: 8,
            copiedChildRecordCount: 2,
        );

        $this->assertSame('rfq-123', $result->createdRfqId);
        $this->assertSame('rfq-456', $result->updatedRfqId);
        $this->assertSame(3, $result->affectedCount);
        $this->assertSame(8, $result->copiedLineItemCount);
        $this->assertSame(2, $result->copiedChildRecordCount);
    }
}
