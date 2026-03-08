<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\QuoteComparisonRun;
use PHPUnit\Framework\TestCase;

final class QuoteComparisonRunTest extends TestCase
{
    private function createRun(string $status = QuoteComparisonRun::STATUS_DRAFT, ?\DateTimeImmutable $expiresAt = null): QuoteComparisonRun
    {
        return new QuoteComparisonRun(
            tenantId: 'tenant-1',
            rfqId: 'RFQ-1',
            name: 'Test Run',
            description: null,
            idempotencyKey: 'idem-1',
            isPreview: true,
            createdBy: 'user-1',
            requestPayload: ['rfq_id' => 'RFQ-1'],
            matrixPayload: ['clusters' => []],
            scoringPayload: ['ranking' => []],
            approvalPayload: [],
            responsePayload: [],
            readinessPayload: [],
            status: $status,
            expiresAt: $expiresAt
        );
    }

    public function testMarkDecisionUpdatesStatusAndResponsePayload(): void
    {
        $run = $this->createRun(QuoteComparisonRun::STATUS_PENDING_APPROVAL);

        $run->markDecision(QuoteComparisonRun::STATUS_APPROVED, ['status' => 'approved']);

        self::assertSame(QuoteComparisonRun::STATUS_APPROVED, $run->getStatus());
        self::assertSame('approved', $run->getApprovalPayload()['status']);
        self::assertSame('approved', $run->getResponsePayload()['status']);
    }

    public function testSavePromotesDraft(): void
    {
        $run = $this->createRun();
        $expiresAt = new \DateTimeImmutable('+1 day');

        $run->save('New Name', 'New Description', QuoteComparisonRun::STATUS_PENDING_APPROVAL, $expiresAt);

        self::assertSame('New Name', $run->getName());
        self::assertSame('New Description', $run->getDescription());
        self::assertSame(QuoteComparisonRun::STATUS_PENDING_APPROVAL, $run->getStatus());
        self::assertFalse($run->isPreview());
        self::assertSame($expiresAt, $run->getExpiresAt());
    }

    public function testSaveThrowsOnDiscarded(): void
    {
        $run = $this->createRun();
        $run->discard('user-1');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot save a discarded comparison run.');

        $run->save('Name', null, null, null);
    }

    public function testDiscardMarksAsDiscarded(): void
    {
        $run = $this->createRun(QuoteComparisonRun::STATUS_PENDING_APPROVAL);

        $run->discard('user-2');

        self::assertSame(QuoteComparisonRun::STATUS_DISCARDED, $run->getStatus());
        self::assertSame('user-2', $run->getDiscardedBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getDiscardedAt());
        self::assertTrue($run->isTerminal());
    }

    public function testDiscardThrowsOnAlreadyDiscarded(): void
    {
        $run = $this->createRun();
        $run->discard('user-1');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Comparison run is already discarded.');

        $run->discard('user-2');
    }

    public function testMarkStaleUpdatesStatus(): void
    {
        $run = $this->createRun(QuoteComparisonRun::STATUS_PENDING_APPROVAL);

        $run->markStale();

        self::assertSame(QuoteComparisonRun::STATUS_STALE, $run->getStatus());
        self::assertTrue($run->isTerminal());
    }

    public function testMarkStaleThrowsOnTerminal(): void
    {
        $run = $this->createRun(QuoteComparisonRun::STATUS_APPROVED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot mark a comparison run in terminal status "approved" as stale.');

        $run->markStale();
    }

    public function testIsExpired(): void
    {
        $notExpired = $this->createRun(QuoteComparisonRun::STATUS_APPROVED, new \DateTimeImmutable('+1 day'));
        self::assertFalse($notExpired->isExpired());

        $expired = $this->createRun(QuoteComparisonRun::STATUS_APPROVED, new \DateTimeImmutable('-1 day'));
        self::assertTrue($expired->isExpired());

        $noExpiry = $this->createRun(QuoteComparisonRun::STATUS_APPROVED, null);
        self::assertFalse($noExpiry->isExpired());
    }

    public function testIsTerminal(): void
    {
        self::assertTrue($this->createRun(QuoteComparisonRun::STATUS_APPROVED)->isTerminal());
        self::assertTrue($this->createRun(QuoteComparisonRun::STATUS_REJECTED)->isTerminal());
        self::assertTrue($this->createRun(QuoteComparisonRun::STATUS_STALE)->isTerminal());
        self::assertTrue($this->createRun(QuoteComparisonRun::STATUS_DISCARDED)->isTerminal());
        self::assertFalse($this->createRun(QuoteComparisonRun::STATUS_DRAFT)->isTerminal());
        self::assertFalse($this->createRun(QuoteComparisonRun::STATUS_PENDING_APPROVAL)->isTerminal());
    }
}
