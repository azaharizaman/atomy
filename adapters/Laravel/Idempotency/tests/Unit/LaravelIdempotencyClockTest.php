<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock;

class LaravelIdempotencyClockTest extends TestCase
{
    private LaravelIdempotencyClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new LaravelIdempotencyClock();
    }

    public function test_now_returns_datetime_immutable(): void
    {
        $result = $this->clock->now();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    public function test_now_returns_current_time(): void
    {
        $before = CarbonImmutable::now();
        $result = $this->clock->now();
        $after = CarbonImmutable::now();
        
        $this->assertGreaterThanOrEqual($before, $result);
        $this->assertLessThanOrEqual($after, $result);
    }
}
