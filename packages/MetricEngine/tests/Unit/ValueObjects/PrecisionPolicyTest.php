<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use PHPUnit\Framework\TestCase;

class PrecisionPolicyTest extends TestCase
{
    public function test_precision_policy_defaults_to_half_up_scale_two(): void
    {
        $policy = PrecisionPolicy::default();

        $this->assertSame(2, $policy->scale);
        $this->assertSame(RoundingMode::HALF_UP, $policy->roundingMode);
    }

    public function test_precision_policy_accepts_custom_scale(): void
    {
        $policy = new PrecisionPolicy(4);

        $this->assertSame(4, $policy->scale);
    }

    public function test_precision_policy_rejects_negative_scale(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Precision scale must be zero or greater.');

        new PrecisionPolicy(-1);
    }
}
