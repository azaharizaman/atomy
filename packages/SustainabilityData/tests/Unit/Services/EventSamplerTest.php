<?php

declare(strict_types=1);

namespace Nexus\SustainabilityData\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\SustainabilityData\Services\EventSampler;
use Nexus\SustainabilityData\Contracts\SustainabilityEventInterface;

final class EventSamplerTest extends TestCase
{
    private $sampler;

    protected function setUp(): void
    {
        $this->sampler = new EventSampler();
    }

    public function test_calculate_sum(): void
    {
        $event1 = $this->createMock(SustainabilityEventInterface::class);
        $event1->method('getValue')->willReturn(10.0);
        $event2 = $this->createMock(SustainabilityEventInterface::class);
        $event2->method('getValue')->willReturn(20.0);

        $this->assertEquals(30.0, $this->sampler->calculateSum([$event1, $event2]));
    }
}
