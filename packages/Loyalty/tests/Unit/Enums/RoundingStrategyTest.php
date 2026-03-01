<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Nexus\Loyalty\Enums\RoundingStrategy;

class RoundingStrategyTest extends TestCase
{
    public function test_floor_rounding(): void
    {
        $this->assertEquals(10, RoundingStrategy::Floor->apply(10.1));
        $this->assertEquals(10, RoundingStrategy::Floor->apply(10.9));
    }

    public function test_ceil_rounding(): void
    {
        $this->assertEquals(11, RoundingStrategy::Ceil->apply(10.1));
        $this->assertEquals(11, RoundingStrategy::Ceil->apply(10.9));
    }

    public function test_nearest_rounding(): void
    {
        $this->assertEquals(10, RoundingStrategy::Nearest->apply(10.4));
        $this->assertEquals(11, RoundingStrategy::Nearest->apply(10.5));
        $this->assertEquals(11, RoundingStrategy::Nearest->apply(10.6));
    }
}
