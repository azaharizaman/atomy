<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\ValueObjects\MetricInput;
use PHPUnit\Framework\TestCase;

class MetricInputTest extends TestCase
{
    public function test_metric_input_rejects_empty_name(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Metric input name is required.');

        new MetricInput('', 10);
    }

    public function test_metric_input_accepts_valid_name_and_value(): void
    {
        $input = new MetricInput('revenue', 1000);

        $this->assertSame('revenue', $input->name);
        $this->assertSame(1000, $input->value);
    }

    public function test_metric_input_accepts_optional_unit(): void
    {
        $input = new MetricInput('weight', 50, 'kg');

        $this->assertSame('kg', $input->unit);
    }

    public function test_metric_input_rejects_whitespace_name(): void
    {
        $this->expectException(FormulaValidationException::class);

        new MetricInput('   ', 10);
    }
}
