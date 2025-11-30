<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Enums;

use Nexus\Messaging\Enums\DeliveryStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\Enums\DeliveryStatus
 */
final class DeliveryStatusTest extends TestCase
{
    public function test_terminal_statuses(): void
    {
        $this->assertTrue(DeliveryStatus::Delivered->isTerminal());
        $this->assertTrue(DeliveryStatus::Failed->isTerminal());
        $this->assertTrue(DeliveryStatus::Bounced->isTerminal());
        $this->assertTrue(DeliveryStatus::Spam->isTerminal());
    }

    public function test_non_terminal_statuses(): void
    {
        $this->assertFalse(DeliveryStatus::Pending->isTerminal());
        $this->assertFalse(DeliveryStatus::Sent->isTerminal());
    }

    public function test_successful_status(): void
    {
        $this->assertTrue(DeliveryStatus::Delivered->isSuccessful());
        
        $this->assertFalse(DeliveryStatus::Pending->isSuccessful());
        $this->assertFalse(DeliveryStatus::Failed->isSuccessful());
    }

    public function test_failed_statuses(): void
    {
        $this->assertTrue(DeliveryStatus::Failed->isFailed());
        $this->assertTrue(DeliveryStatus::Bounced->isFailed());
        $this->assertTrue(DeliveryStatus::Spam->isFailed());
        
        $this->assertFalse(DeliveryStatus::Delivered->isFailed());
        $this->assertFalse(DeliveryStatus::Pending->isFailed());
    }

    public function test_labels(): void
    {
        $this->assertSame('Pending', DeliveryStatus::Pending->label());
        $this->assertSame('Delivered', DeliveryStatus::Delivered->label());
        $this->assertSame('Failed', DeliveryStatus::Failed->label());
    }
}
