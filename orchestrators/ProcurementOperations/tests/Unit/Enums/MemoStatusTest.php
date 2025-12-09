<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\MemoStatus;
use PHPUnit\Framework\TestCase;

final class MemoStatusTest extends TestCase
{
    public function test_draft_status_is_editable(): void
    {
        $this->assertTrue(MemoStatus::DRAFT->canEdit());
    }

    public function test_pending_approval_status_is_not_editable(): void
    {
        $this->assertFalse(MemoStatus::PENDING_APPROVAL->canEdit());
    }

    public function test_approved_status_can_be_applied(): void
    {
        $this->assertTrue(MemoStatus::APPROVED->canApply());
    }

    public function test_draft_status_cannot_be_applied(): void
    {
        $this->assertFalse(MemoStatus::DRAFT->canApply());
    }

    public function test_applied_status_cannot_be_cancelled(): void
    {
        $this->assertFalse(MemoStatus::APPLIED->canCancel());
    }

    public function test_draft_status_can_be_cancelled(): void
    {
        $this->assertTrue(MemoStatus::DRAFT->canCancel());
    }

    public function test_cancelled_status_is_final(): void
    {
        $status = MemoStatus::CANCELLED;

        $this->assertFalse($status->canEdit());
        $this->assertFalse($status->canApply());
        $this->assertFalse($status->canCancel());
    }

    public function test_all_statuses_have_labels(): void
    {
        foreach (MemoStatus::cases() as $status) {
            $this->assertNotEmpty($status->getLabel());
        }
    }

    public function test_status_labels_are_human_readable(): void
    {
        $this->assertSame('Draft', MemoStatus::DRAFT->getLabel());
        $this->assertSame('Pending Approval', MemoStatus::PENDING_APPROVAL->getLabel());
        $this->assertSame('Approved', MemoStatus::APPROVED->getLabel());
        $this->assertSame('Rejected', MemoStatus::REJECTED->getLabel());
        $this->assertSame('Applied', MemoStatus::APPLIED->getLabel());
        $this->assertSame('Cancelled', MemoStatus::CANCELLED->getLabel());
    }

    public function test_pending_approval_can_be_cancelled(): void
    {
        $this->assertTrue(MemoStatus::PENDING_APPROVAL->canCancel());
    }

    public function test_approved_can_be_cancelled(): void
    {
        $this->assertTrue(MemoStatus::APPROVED->canCancel());
    }
}
