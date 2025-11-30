<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Enums;

use Nexus\Messaging\Enums\Direction;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\Enums\Direction
 */
final class DirectionTest extends TestCase
{
    public function testInboundDirection(): void
    {
        $direction = Direction::Inbound;
        
        $this->assertTrue($direction->isInbound());
        $this->assertFalse($direction->isOutbound());
        $this->assertSame('Inbound', $direction->label());
        $this->assertSame('inbound', $direction->value);
    }

    public function testOutboundDirection(): void
    {
        $direction = Direction::Outbound;
        
        $this->assertTrue($direction->isOutbound());
        $this->assertFalse($direction->isInbound());
        $this->assertSame('Outbound', $direction->label());
        $this->assertSame('outbound', $direction->value);
    }

    public function testAllDirectionsExist(): void
    {
        $this->assertCount(2, Direction::cases());
        $this->assertContains(Direction::Inbound, Direction::cases());
        $this->assertContains(Direction::Outbound, Direction::cases());
    }
}
