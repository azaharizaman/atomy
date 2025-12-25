<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\VirtualCardStatus;
use PHPUnit\Framework\TestCase;

final class VirtualCardStatusTest extends TestCase
{
    public function test_label_is_defined_for_all_statuses(): void
    {
        foreach (VirtualCardStatus::cases() as $status) {
            self::assertNotSame('', $status->label());
        }
    }

    public function test_usability_final_and_transition_helpers(): void
    {
        self::assertTrue(VirtualCardStatus::ACTIVE->isUsable());
        self::assertFalse(VirtualCardStatus::PENDING->isUsable());

        self::assertTrue(VirtualCardStatus::USED->isFinal());
        self::assertTrue(VirtualCardStatus::EXPIRED->isFinal());
        self::assertTrue(VirtualCardStatus::CANCELLED->isFinal());
        self::assertFalse(VirtualCardStatus::ACTIVE->isFinal());

        self::assertTrue(VirtualCardStatus::PENDING->canCancel());
        self::assertTrue(VirtualCardStatus::ACTIVE->canCancel());
        self::assertTrue(VirtualCardStatus::SUSPENDED->canCancel());
        self::assertFalse(VirtualCardStatus::USED->canCancel());

        self::assertTrue(VirtualCardStatus::ACTIVE->canSuspend());
        self::assertFalse(VirtualCardStatus::PENDING->canSuspend());

        self::assertTrue(VirtualCardStatus::SUSPENDED->canReactivate());
        self::assertFalse(VirtualCardStatus::ACTIVE->canReactivate());
    }
}
