<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\CheckStatus;
use PHPUnit\Framework\TestCase;

final class CheckStatusTest extends TestCase
{
    public function test_label_is_defined_for_all_statuses(): void
    {
        foreach (CheckStatus::cases() as $status) {
            self::assertNotSame('', $status->label());
        }
    }

    public function test_outstanding_final_and_transition_helpers(): void
    {
        self::assertTrue(CheckStatus::PENDING->isOutstanding());
        self::assertTrue(CheckStatus::MAILED->isOutstanding());
        self::assertFalse(CheckStatus::CLEARED->isOutstanding());

        self::assertTrue(CheckStatus::CLEARED->isFinal());
        self::assertTrue(CheckStatus::VOIDED->isFinal());
        self::assertTrue(CheckStatus::EXPIRED->isFinal());
        self::assertFalse(CheckStatus::STOP_PAYMENT->isFinal());

        self::assertTrue(CheckStatus::PRINTED->canStopPayment());
        self::assertFalse(CheckStatus::PENDING->canStopPayment());
        self::assertFalse(CheckStatus::CLEARED->canStopPayment());

        self::assertTrue(CheckStatus::MAILED->canVoid());
        self::assertFalse(CheckStatus::REISSUED->canVoid());

        self::assertTrue(CheckStatus::STOP_PAYMENT->canReissue());
        self::assertTrue(CheckStatus::RETURNED->canReissue());
        self::assertFalse(CheckStatus::PENDING->canReissue());
    }

    public function test_validTransitions_defines_expected_next_steps_for_common_states(): void
    {
        self::assertSame([
            CheckStatus::PRINTED,
            CheckStatus::VOIDED,
        ], CheckStatus::PENDING->validTransitions());

        self::assertSame([
            CheckStatus::MAILED,
            CheckStatus::VOIDED,
            CheckStatus::STOP_PAYMENT,
        ], CheckStatus::PRINTED->validTransitions());

        self::assertSame([
            CheckStatus::CLEARED,
            CheckStatus::VOIDED,
            CheckStatus::STOP_PAYMENT,
            CheckStatus::RETURNED,
            CheckStatus::EXPIRED,
        ], CheckStatus::MAILED->validTransitions());

        self::assertSame([], CheckStatus::CLEARED->validTransitions());
        self::assertSame([], CheckStatus::REISSUED->validTransitions());
    }
}
