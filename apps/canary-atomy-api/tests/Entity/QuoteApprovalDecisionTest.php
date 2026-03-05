<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\QuoteApprovalDecision;
use App\Entity\QuoteComparisonRun;
use PHPUnit\Framework\TestCase;

final class QuoteApprovalDecisionTest extends TestCase
{
    public function testAccessorsReturnStoredValues(): void
    {
        $run = new QuoteComparisonRun(
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            idempotencyKey: null,
            requestPayload: [],
            matrixPayload: [],
            scoringPayload: [],
            approvalPayload: ['status' => 'pending_approval'],
            responsePayload: [],
            status: 'pending_approval'
        );

        $decision = new QuoteApprovalDecision(
            comparisonRun: $run,
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            decision: 'approve',
            reason: 'approved by procurement lead',
            decidedBy: 'procurement@example.com'
        );

        self::assertSame('approve', $decision->getDecision());
        self::assertSame('approved by procurement lead', $decision->getReason());
        self::assertSame('procurement@example.com', $decision->getDecidedBy());
    }
}
