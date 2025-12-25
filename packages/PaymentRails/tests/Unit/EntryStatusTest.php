<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\EntryStatus;
use PHPUnit\Framework\TestCase;

final class EntryStatusTest extends TestCase
{
    public function test_label_is_defined_for_all_statuses(): void
    {
        foreach (EntryStatus::cases() as $status) {
            self::assertNotSame('', $status->label());
        }
    }

    public function test_success_failure_final_and_action_flags(): void
    {
        self::assertTrue(EntryStatus::SETTLED->isSuccess());
        self::assertFalse(EntryStatus::PENDING->isSuccess());

        self::assertTrue(EntryStatus::RETURNED->isFailure());
        self::assertTrue(EntryStatus::REJECTED->isFailure());
        self::assertTrue(EntryStatus::CANCELLED->isFailure());
        self::assertFalse(EntryStatus::PROCESSING->isFailure());

        self::assertTrue(EntryStatus::SETTLED->isFinal());
        self::assertTrue(EntryStatus::RETURNED->isFinal());
        self::assertTrue(EntryStatus::REJECTED->isFinal());
        self::assertTrue(EntryStatus::CANCELLED->isFinal());
        self::assertFalse(EntryStatus::TRANSMITTED->isFinal());

        self::assertTrue(EntryStatus::RETURNED->requiresAction());
        self::assertTrue(EntryStatus::NOC_RECEIVED->requiresAction());
        self::assertTrue(EntryStatus::ON_HOLD->requiresAction());
        self::assertFalse(EntryStatus::SETTLED->requiresAction());
    }

    public function test_cancel_and_retry_rules(): void
    {
        self::assertTrue(EntryStatus::PENDING->canCancel());
        self::assertTrue(EntryStatus::ON_HOLD->canCancel());
        self::assertFalse(EntryStatus::BATCHED->canCancel());

        self::assertTrue(EntryStatus::RETURNED->canRetry());
        self::assertTrue(EntryStatus::REJECTED->canRetry());
        self::assertFalse(EntryStatus::CANCELLED->canRetry());
    }
}
