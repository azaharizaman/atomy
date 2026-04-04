<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Tests\Unit;

use Nexus\Sourcing\ValueObjects\RfqLifecycleResult;
use Nexus\Sourcing\Exceptions\RfqLifecycleException;
use PHPUnit\Framework\TestCase;

final class RfqLifecycleResultTest extends TestCase
{
    public function test_result_carries_created_and_updated_identifiers_and_counts(): void
    {
        $result = new RfqLifecycleResult(
            action: 'duplicate',
            status: 'draft',
            rfqId: 'rfq-123',
            sourceRfqId: 'rfq-456',
            affectedCount: 1,
            invitationId: 'inv-1',
            copiedLineItemCount: 8,
            copiedChildRecordCount: 2,
        );

        $this->assertSame('duplicate', $result->action);
        $this->assertSame('draft', $result->status);
        $this->assertSame('rfq-123', $result->rfqId);
        $this->assertSame('rfq-456', $result->sourceRfqId);
        $this->assertSame(1, $result->affectedCount);
        $this->assertSame('inv-1', $result->invitationId);
        $this->assertSame(8, $result->copiedLineItemCount);
        $this->assertSame(2, $result->copiedChildRecordCount);
    }

    public function test_result_rejects_empty_identifier_strings(): void
    {
        $this->expectException(RfqLifecycleException::class);

        new RfqLifecycleResult(
            action: 'duplicate',
            status: 'draft',
            rfqId: '   ',
        );
    }
}
