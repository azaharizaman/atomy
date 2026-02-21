<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Test\Unit\Entities;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;
use PHPUnit\Framework\TestCase;

class AssetRevaluationTest extends TestCase
{
    public function testConstructorInitializesAllProperties(): void
    {
        $id = 'rev_001';
        $assetId = 'asset_001';
        $tenantId = 'tenant_001';
        $revaluationDate = new DateTimeImmutable('2024-01-15');
        $revaluationType = RevaluationType::INCREMENT;
        $previousBookValue = new BookValue(10000.0, 1000.0, 5000.0);
        $newBookValue = new BookValue(12000.0, 1000.0, 5000.0);
        $revaluationAmount = RevaluationAmount::fromValues(10000.0, 12000.0, 'USD');
        $glAccountId = 'gl_001';
        $reason = 'Market value increase';
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $journalEntryId = 'je_001';
        $postedAt = new DateTimeImmutable('2024-01-16 09:00:00');
        $scheduleId = 'sch_001';
        $reversesRevaluationId = 'rev_original';
        $status = 'posted';

        $revaluation = new AssetRevaluation(
            id: $id,
            assetId: $assetId,
            tenantId: $tenantId,
            revaluationDate: $revaluationDate,
            revaluationType: $revaluationType,
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            revaluationAmount: $revaluationAmount,
            glAccountId: $glAccountId,
            reason: $reason,
            createdAt: $createdAt,
            journalEntryId: $journalEntryId,
            postedAt: $postedAt,
            scheduleId: $scheduleId,
            reversesRevaluationId: $reversesRevaluationId,
            status: $status,
        );

        self::assertSame($id, $revaluation->id);
        self::assertSame($assetId, $revaluation->assetId);
        self::assertSame($tenantId, $revaluation->tenantId);
        self::assertSame($revaluationDate, $revaluation->revaluationDate);
        self::assertSame($revaluationType, $revaluation->revaluationType);
        self::assertSame($previousBookValue, $revaluation->previousBookValue);
        self::assertSame($newBookValue, $revaluation->newBookValue);
        self::assertSame($revaluationAmount, $revaluation->revaluationAmount);
        self::assertSame($glAccountId, $revaluation->glAccountId);
        self::assertSame($reason, $revaluation->reason);
        self::assertSame($createdAt, $revaluation->createdAt);
        self::assertSame($journalEntryId, $revaluation->journalEntryId);
        self::assertSame($postedAt, $revaluation->postedAt);
        self::assertSame($scheduleId, $revaluation->scheduleId);
        self::assertSame($reversesRevaluationId, $revaluation->reversesRevaluationId);
        self::assertSame($status, $revaluation->status);
    }

    public function testConstructorWithOptionalParametersAsNull(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_002',
            assetId: 'asset_002',
            tenantId: 'tenant_002',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(11000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 11000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertNull($revaluation->journalEntryId);
        self::assertNull($revaluation->postedAt);
        self::assertNull($revaluation->scheduleId);
        self::assertNull($revaluation->reversesRevaluationId);
        self::assertSame('pending', $revaluation->status);
    }

    public function testCreateStaticMethod(): void
    {
        $previousBookValue = new BookValue(10000.0, 1000.0, 5000.0);
        $newBookValue = new BookValue(12000.0, 1000.0, 5000.0);

        $revaluation = AssetRevaluation::create(
            assetId: 'asset_003',
            tenantId: 'tenant_003',
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            type: RevaluationType::INCREMENT,
            reason: 'Market appreciation',
        );

        self::assertStringStartsWith('rev_', $revaluation->id);
        self::assertSame('asset_003', $revaluation->assetId);
        self::assertSame('tenant_003', $revaluation->tenantId);
        self::assertSame(RevaluationType::INCREMENT, $revaluation->revaluationType);
        self::assertSame($previousBookValue, $revaluation->previousBookValue);
        self::assertSame($newBookValue, $revaluation->newBookValue);
        self::assertSame('Market appreciation', $revaluation->reason);
        self::assertSame('pending', $revaluation->status);
        self::assertNull($revaluation->glAccountId);
    }

    public function testCreateWithCustomDate(): void
    {
        $customDate = new DateTimeImmutable('2024-06-15');
        $previousBookValue = new BookValue(10000.0, 1000.0, 5000.0);
        $newBookValue = new BookValue(11500.0, 1000.0, 5000.0);

        $revaluation = AssetRevaluation::create(
            assetId: 'asset_004',
            tenantId: 'tenant_004',
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            type: RevaluationType::INCREMENT,
            reason: 'Test',
            date: $customDate,
        );

        self::assertSame($customDate, $revaluation->revaluationDate);
    }

    public function testCreateWithGlAccountId(): void
    {
        $previousBookValue = new BookValue(10000.0, 1000.0, 5000.0);
        $newBookValue = new BookValue(11000.0, 1000.0, 5000.0);

        $revaluation = AssetRevaluation::create(
            assetId: 'asset_005',
            tenantId: 'tenant_005',
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            type: RevaluationType::DECREMENT,
            reason: 'Impairment',
            glAccountId: 'gl_impaired',
        );

        self::assertSame('gl_impaired', $revaluation->glAccountId);
    }

    public function testIsIncrementReturnsTrue(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_003',
            assetId: 'asset_006',
            tenantId: 'tenant_006',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertTrue($revaluation->isIncrement());
    }

    public function testIsIncrementReturnsFalse(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_004',
            assetId: 'asset_007',
            tenantId: 'tenant_007',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::DECREMENT,
            previousBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(12000.0, 10000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertFalse($revaluation->isIncrement());
    }

    public function testIsDecrementReturnsTrue(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_005',
            assetId: 'asset_008',
            tenantId: 'tenant_008',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::DECREMENT,
            previousBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(12000.0, 10000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertTrue($revaluation->isDecrement());
    }

    public function testIsDecrementReturnsFalse(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_006',
            assetId: 'asset_009',
            tenantId: 'tenant_009',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertFalse($revaluation->isDecrement());
    }

    public function testIsPostedReturnsTrue(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_007',
            assetId: 'asset_010',
            tenantId: 'tenant_010',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            journalEntryId: 'je_001',
            postedAt: new DateTimeImmutable(),
            status: 'posted',
        );

        self::assertTrue($revaluation->isPosted());
    }

    public function testIsPostedReturnsFalseWhenStatusNotPosted(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_008',
            assetId: 'asset_011',
            tenantId: 'tenant_011',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            journalEntryId: 'je_001',
            postedAt: new DateTimeImmutable(),
            status: 'pending',
        );

        self::assertFalse($revaluation->isPosted());
    }

    public function testIsPostedReturnsFalseWhenNoJournalEntry(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_009',
            assetId: 'asset_012',
            tenantId: 'tenant_012',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            status: 'posted',
        );

        self::assertFalse($revaluation->isPosted());
    }

    public function testIsPendingReturnsTrue(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_010',
            assetId: 'asset_013',
            tenantId: 'tenant_013',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            status: 'pending',
        );

        self::assertTrue($revaluation->isPending());
    }

    public function testIsPendingReturnsFalse(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_011',
            assetId: 'asset_014',
            tenantId: 'tenant_014',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            status: 'posted',
        );

        self::assertFalse($revaluation->isPending());
    }

    public function testIsReversedReturnsTrue(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_012',
            assetId: 'asset_015',
            tenantId: 'tenant_015',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            status: 'reversed',
        );

        self::assertTrue($revaluation->isReversed());
    }

    public function testIsReversedReturnsFalse(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_013',
            assetId: 'asset_016',
            tenantId: 'tenant_016',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
            status: 'pending',
        );

        self::assertFalse($revaluation->isReversed());
    }

    public function testGetAmount(): void
    {
        $revaluationAmount = RevaluationAmount::fromValues(10000.0, 12000.0, 'USD');
        $revaluation = new AssetRevaluation(
            id: 'rev_014',
            assetId: 'asset_017',
            tenantId: 'tenant_017',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: $revaluationAmount,
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        self::assertSame(2000.0, $revaluation->getAmount());
    }

    public function testGetPreviousNetBookValue(): void
    {
        $previousBookValue = new BookValue(10000.0, 1000.0, 5000.0);
        $revaluation = new AssetRevaluation(
            id: 'rev_015',
            assetId: 'asset_018',
            tenantId: 'tenant_018',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: $previousBookValue,
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        // Net book value = cost - accumulated depreciation = 10000 - 5000 = 5000
        self::assertSame(5000.0, $revaluation->getPreviousNetBookValue());
    }

    public function testGetNewNetBookValue(): void
    {
        $newBookValue = new BookValue(12000.0, 1000.0, 5000.0);
        $revaluation = new AssetRevaluation(
            id: 'rev_016',
            assetId: 'asset_019',
            tenantId: 'tenant_019',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: $newBookValue,
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        // Net book value = cost - accumulated depreciation = 12000 - 5000 = 7000
        self::assertSame(7000.0, $revaluation->getNewNetBookValue());
    }

    public function testWithPosting(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_017',
            assetId: 'asset_020',
            tenantId: 'tenant_020',
            revaluationDate: new DateTimeImmutable('2024-01-15'),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: 'gl_001',
            reason: 'Test',
            createdAt: new DateTimeImmutable('2024-01-15'),
        );

        $posted = $revaluation->withPosting('je_new_001');

        self::assertSame('je_new_001', $posted->journalEntryId);
        self::assertNotNull($posted->postedAt);
        self::assertSame('posted', $posted->status);
        // Original should be unchanged
        self::assertNull($revaluation->journalEntryId);
    }

    public function testAsReversal(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_018',
            assetId: 'asset_021',
            tenantId: 'tenant_021',
            revaluationDate: new DateTimeImmutable('2024-01-15'),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: 'gl_001',
            reason: 'Original revaluation',
            createdAt: new DateTimeImmutable('2024-01-15'),
        );

        $reversal = $revaluation->asReversal('rev_018');

        self::assertStringStartsWith('rev_', $reversal->id);
        self::assertSame(RevaluationType::DECREMENT, $reversal->revaluationType);
        self::assertSame('rev_018', $reversal->reversesRevaluationId);
        self::assertStringContainsString('Reversal:', $reversal->reason);
        self::assertSame('pending', $reversal->status);
    }

    public function testWithSchedule(): void
    {
        $revaluation = new AssetRevaluation(
            id: 'rev_019',
            assetId: 'asset_022',
            tenantId: 'tenant_022',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: null,
            reason: 'Test',
            createdAt: new DateTimeImmutable(),
        );

        $linked = $revaluation->withSchedule('sch_new_001');

        self::assertSame('sch_new_001', $linked->scheduleId);
        // Original should be unchanged
        self::assertNull($revaluation->scheduleId);
    }

    public function testToArray(): void
    {
        $revaluationDate = new DateTimeImmutable('2024-03-15');
        $createdAt = new DateTimeImmutable('2024-03-15 10:00:00');
        $postedAt = new DateTimeImmutable('2024-03-16 09:00:00');

        $revaluation = new AssetRevaluation(
            id: 'rev_020',
            assetId: 'asset_023',
            tenantId: 'tenant_023',
            revaluationDate: $revaluationDate,
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 5000.0),
            newBookValue: new BookValue(12000.0, 1000.0, 5000.0),
            revaluationAmount: RevaluationAmount::fromValues(10000.0, 12000.0, 'USD'),
            glAccountId: 'gl_001',
            reason: 'Market increase',
            createdAt: $createdAt,
            journalEntryId: 'je_001',
            postedAt: $postedAt,
            scheduleId: 'sch_001',
            status: 'posted',
        );

        $array = $revaluation->toArray();

        self::assertSame('rev_020', $array['id']);
        self::assertSame('asset_023', $array['assetId']);
        self::assertSame('tenant_023', $array['tenantId']);
        self::assertSame('2024-03-15', $array['revaluationDate']);
        self::assertSame('increment', $array['revaluationType']);
        self::assertSame(5000.0, $array['previousBookValue']); // Net book value
        self::assertSame(7000.0, $array['newBookValue']); // Net book value
        self::assertSame(2000.0, $array['revaluationAmount']);
        self::assertSame('gl_001', $array['glAccountId']);
        self::assertSame('Market increase', $array['reason']);
        self::assertSame('posted', $array['status']);
        self::assertTrue($array['isPosted']);
        self::assertFalse($array['isReversed']);
        self::assertSame('je_001', $array['journalEntryId']);
        self::assertSame('2024-03-16 09:00:00', $array['postedAt']);
        self::assertSame('2024-03-15 10:00:00', $array['createdAt']);
    }
}
