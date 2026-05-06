<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\PeriodGranularity;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\PeriodKey;
use PHPUnit\Framework\TestCase;

class PeriodKeyTest extends TestCase
{
    public function test_parses_supported_period_shapes(): void
    {
        $this->assertSame(PeriodGranularity::DATE, PeriodKey::fromString('2026-05-06')->granularity);
        $this->assertSame(PeriodGranularity::MONTH, PeriodKey::fromString('2026-05')->granularity);
        $this->assertSame(PeriodGranularity::QUARTER, PeriodKey::fromString('2026-Q2')->granularity);
        $this->assertSame(PeriodGranularity::YEAR, PeriodKey::fromString('2026')->granularity);
    }

    public function test_rejects_invalid_period_shape(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Unsupported period key [2026-5].');

        PeriodKey::fromString('2026-5');
    }

    public function test_rejects_invalid_calendar_date(): void
    {
        $this->expectException(InvalidWindowException::class);
        $this->expectExceptionMessage('Unsupported period key [2026-02-31].');

        PeriodKey::fromString('2026-02-31');
    }
}
