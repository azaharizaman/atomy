<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\QuoteComparisonRun;
use App\Entity\QuoteDecisionTrailEntry;
use PHPUnit\Framework\TestCase;

final class QuoteDecisionTrailEntryTest extends TestCase
{
    public function testAccessorsReturnStoredValues(): void
    {
        $run = new QuoteComparisonRun(
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            name: 'Test Run',
            description: null,
            idempotencyKey: null,
            isPreview: false,
            createdBy: 'user-1',
            requestPayload: [],
            matrixPayload: [],
            scoringPayload: [],
            approvalPayload: ['status' => 'pending_approval'],
            responsePayload: [],
            readinessPayload: [],
            status: 'pending_approval'
        );

        $entry = new QuoteDecisionTrailEntry(
            comparisonRun: $run,
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            sequence: 3,
            eventType: 'approval_override',
            payloadHash: str_repeat('a', 64),
            previousHash: str_repeat('b', 64),
            entryHash: str_repeat('c', 64),
            occurredAt: new \DateTimeImmutable()
        );

        self::assertSame(3, $entry->getSequence());
        self::assertSame('approval_override', $entry->getEventType());
        self::assertSame(str_repeat('c', 64), $entry->getEntryHash());
    }
}
