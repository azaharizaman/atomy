<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\InvalidNumericValueException;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\Exceptions\MissingInputException;
use Nexus\MetricEngine\Exceptions\TypeMismatchException;
use Nexus\MetricEngine\Services\MetricStatusInferenceService;
use PHPUnit\Framework\TestCase;

class MetricStatusInferenceServiceTest extends TestCase
{
    private MetricStatusInferenceService $service;

    protected function setUp(): void
    {
        $this->service = new MetricStatusInferenceService();
    }

    public function test_maps_known_exceptions_to_statuses(): void
    {
        $this->assertSame(MetricResultStatus::NO_DATA, $this->service->infer(new InsufficientDataException('empty')));
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $this->service->infer(new MissingInputException('revenue')));
        $this->assertSame(MetricResultStatus::NOT_AVAILABLE, $this->service->infer(new DivideByZeroMetricException()));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new InvalidNumericValueException('bad')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new InvalidWindowException('bad window')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new TypeMismatchException('bad type')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new FormulaValidationException('bad formula')));
        $this->assertSame(MetricResultStatus::ERROR, $this->service->infer(new \RuntimeException('unexpected')));
    }
}
