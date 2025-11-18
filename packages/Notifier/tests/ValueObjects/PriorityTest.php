<?php

declare(strict_types=1);

namespace Nexus\Notifier\Tests\ValueObjects;

use Nexus\Notifier\ValueObjects\Priority;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Priority::class)]
final class PriorityTest extends TestCase
{
    #[Test]
    public function it_has_correct_enum_cases(): void
    {
        $this->assertSame('low', Priority::Low->value);
        $this->assertSame('normal', Priority::Normal->value);
        $this->assertSame('high', Priority::High->value);
        $this->assertSame('critical', Priority::Critical->value);
    }

    #[Test]
    #[DataProvider('priorityWeightProvider')]
    public function it_returns_correct_weight(Priority $priority, int $expectedWeight): void
    {
        $this->assertSame($expectedWeight, $priority->getWeight());
    }

    public static function priorityWeightProvider(): array
    {
        return [
            'Low priority has weight 10' => [Priority::Low, 10],
            'Normal priority has weight 20' => [Priority::Normal, 20],
            'High priority has weight 30' => [Priority::High, 30],
            'Critical priority has weight 40' => [Priority::Critical, 40],
        ];
    }

    #[Test]
    #[DataProvider('rateLimitBypassProvider')]
    public function it_correctly_identifies_rate_limit_bypass(Priority $priority, bool $expectedBypass): void
    {
        $this->assertSame($expectedBypass, $priority->bypassesRateLimit());
    }

    public static function rateLimitBypassProvider(): array
    {
        return [
            'Low priority does not bypass rate limit' => [Priority::Low, false],
            'Normal priority does not bypass rate limit' => [Priority::Normal, false],
            'High priority does not bypass rate limit' => [Priority::High, false],
            'Critical priority bypasses rate limit' => [Priority::Critical, true],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $this->assertSame(Priority::Low, Priority::from('low'));
        $this->assertSame(Priority::Normal, Priority::from('normal'));
        $this->assertSame(Priority::High, Priority::from('high'));
        $this->assertSame(Priority::Critical, Priority::from('critical'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        Priority::from('invalid');
    }
}
