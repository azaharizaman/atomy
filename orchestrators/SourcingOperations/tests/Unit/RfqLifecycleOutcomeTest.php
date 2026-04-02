<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Tests\Unit;

use Nexus\SourcingOperations\DTOs\RfqLifecycleOutcome;
use PHPUnit\Framework\TestCase;

final class RfqLifecycleOutcomeTest extends TestCase
{
    public function test_outcome_carries_summary_details(): void
    {
        $outcome = new RfqLifecycleOutcome(
            action: 'duplicate',
            tenantId: 'tenant-001',
            status: 'draft',
            rfqId: 'rfq-200',
            sourceRfqId: 'rfq-100',
            affectedCount: 1,
            invitationId: 'inv-1',
            copiedLineItemCount: 4,
            copiedChildRecordCount: 0,
        );

        self::assertSame('duplicate', $outcome->action);
        self::assertSame('tenant-001', $outcome->tenantId);
        self::assertSame('draft', $outcome->status);
        self::assertSame('rfq-200', $outcome->rfqId);
        self::assertSame('rfq-100', $outcome->sourceRfqId);
        self::assertSame(1, $outcome->affectedCount);
        self::assertSame('inv-1', $outcome->invitationId);
        self::assertSame(4, $outcome->copiedLineItemCount);
        self::assertSame(0, $outcome->copiedChildRecordCount);
    }

    public function test_outcome_rejects_invalid_counts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RfqLifecycleOutcome(
            action: 'bulk_action',
            tenantId: 'tenant-001',
            status: 'closed',
            affectedCount: -1,
        );
    }
}
