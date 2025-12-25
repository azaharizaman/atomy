<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Tests\Unit;

use Nexus\PaymentRails\Enums\FileStatus;
use PHPUnit\Framework\TestCase;

final class FileStatusTest extends TestCase
{
    public function test_label_is_defined_for_all_statuses(): void
    {
        foreach (FileStatus::cases() as $status) {
            self::assertNotSame('', $status->label());
        }
    }

    public function test_success_failure_processing_and_final_flags(): void
    {
        self::assertTrue(FileStatus::TRANSMITTED->isSuccess());
        self::assertTrue(FileStatus::ACKNOWLEDGED->isSuccess());
        self::assertTrue(FileStatus::ACCEPTED->isSuccess());
        self::assertFalse(FileStatus::GENERATED->isSuccess());

        self::assertTrue(FileStatus::REJECTED->isFailure());
        self::assertTrue(FileStatus::FAILED->isFailure());
        self::assertTrue(FileStatus::CANCELLED->isFailure());
        self::assertFalse(FileStatus::APPROVED->isFailure());

        self::assertTrue(FileStatus::ACCEPTED->isFinal());
        self::assertTrue(FileStatus::PARTIALLY_ACCEPTED->isFinal());
        self::assertTrue(FileStatus::REJECTED->isFinal());
        self::assertTrue(FileStatus::CANCELLED->isFinal());
        self::assertFalse(FileStatus::TRANSMITTING->isFinal());

        self::assertTrue(FileStatus::TRANSMITTING->isProcessing());
        self::assertTrue(FileStatus::TRANSMITTED->isProcessing());
        self::assertTrue(FileStatus::ACKNOWLEDGED->isProcessing());
        self::assertFalse(FileStatus::APPROVED->isProcessing());
    }

    public function test_canRetransmit_and_canCancel_rules(): void
    {
        self::assertTrue(FileStatus::FAILED->canRetransmit());
        self::assertTrue(FileStatus::REJECTED->canRetransmit());
        self::assertFalse(FileStatus::CANCELLED->canRetransmit());

        self::assertTrue(FileStatus::GENERATED->canCancel());
        self::assertTrue(FileStatus::PENDING_APPROVAL->canCancel());
        self::assertTrue(FileStatus::APPROVED->canCancel());
        self::assertFalse(FileStatus::TRANSMITTED->canCancel());
    }
}
