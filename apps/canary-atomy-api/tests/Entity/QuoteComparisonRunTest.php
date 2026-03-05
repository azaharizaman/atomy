<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\QuoteComparisonRun;
use PHPUnit\Framework\TestCase;

final class QuoteComparisonRunTest extends TestCase
{
    public function testMarkDecisionUpdatesStatusAndResponsePayload(): void
    {
        $run = new QuoteComparisonRun(
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            idempotencyKey: 'idem-1',
            requestPayload: ['rfq_id' => 'RFQ-1'],
            matrixPayload: ['clusters' => []],
            scoringPayload: ['ranking' => []],
            approvalPayload: ['status' => 'pending_approval'],
            responsePayload: ['status' => 'pending_approval'],
            status: 'pending_approval'
        );

        $run->markDecision('approved', ['status' => 'approved']);

        self::assertSame('approved', $run->getStatus());
        self::assertSame('approved', $run->getApprovalPayload()['status']);
        self::assertSame('approved', $run->getResponsePayload()['status']);
    }
}
