<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use PHPUnit\Framework\TestCase;

final class VendorApprovalRecordTest extends TestCase
{
    public function test_it_rejects_empty_approver_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Approved by user ID cannot be empty.');

        new VendorApprovalRecord('   ', new DateTimeImmutable());
    }

    public function test_it_trims_approver_id_and_preserves_note(): void
    {
        $approvedAt = new DateTimeImmutable('2026-04-21 08:15:00 UTC');
        $record = new VendorApprovalRecord('  user-123  ', $approvedAt, '  approved after manual review  ');

        self::assertSame('user-123', $record->getApprovedByUserId());
        self::assertSame($approvedAt, $record->getApprovedAt());
        self::assertSame('approved after manual review', $record->getApprovalNote());
    }

    public function test_it_normalizes_blank_note_to_null(): void
    {
        $recordWithBlankNote = new VendorApprovalRecord('user-123', new DateTimeImmutable(), '   ');
        $recordWithNullNote = new VendorApprovalRecord('user-123', new DateTimeImmutable(), null);

        self::assertNull($recordWithBlankNote->getApprovalNote());
        self::assertNull($recordWithNullNote->getApprovalNote());
    }
}
