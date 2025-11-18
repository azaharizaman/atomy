<?php

declare(strict_types=1);

namespace Nexus\Notifier\Tests\ValueObjects;

use Nexus\Notifier\ValueObjects\DeliveryStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeliveryStatus::class)]
final class DeliveryStatusTest extends TestCase
{
    #[Test]
    public function it_has_correct_enum_cases(): void
    {
        $this->assertSame('pending', DeliveryStatus::Pending->value);
        $this->assertSame('sent', DeliveryStatus::Sent->value);
        $this->assertSame('delivered', DeliveryStatus::Delivered->value);
        $this->assertSame('failed', DeliveryStatus::Failed->value);
        $this->assertSame('bounced', DeliveryStatus::Bounced->value);
        $this->assertSame('read', DeliveryStatus::Read->value);
    }

    #[Test]
    #[DataProvider('finalStatusProvider')]
    public function it_correctly_identifies_final_statuses(DeliveryStatus $status, bool $expectedFinal): void
    {
        $this->assertSame($expectedFinal, $status->isFinal());
    }

    public static function finalStatusProvider(): array
    {
        return [
            'Pending is not final' => [DeliveryStatus::Pending, false],
            'Sent is not final' => [DeliveryStatus::Sent, false],
            'Delivered is final' => [DeliveryStatus::Delivered, true],
            'Failed is final' => [DeliveryStatus::Failed, true],
            'Bounced is final' => [DeliveryStatus::Bounced, true],
            'Read is final' => [DeliveryStatus::Read, true],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $this->assertSame(DeliveryStatus::Pending, DeliveryStatus::from('pending'));
        $this->assertSame(DeliveryStatus::Sent, DeliveryStatus::from('sent'));
        $this->assertSame(DeliveryStatus::Delivered, DeliveryStatus::from('delivered'));
        $this->assertSame(DeliveryStatus::Failed, DeliveryStatus::from('failed'));
        $this->assertSame(DeliveryStatus::Bounced, DeliveryStatus::from('bounced'));
        $this->assertSame(DeliveryStatus::Read, DeliveryStatus::from('read'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        DeliveryStatus::from('invalid');
    }
}
