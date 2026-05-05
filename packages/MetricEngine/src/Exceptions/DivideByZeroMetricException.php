<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class DivideByZeroMetricException extends MetricEngineException
{
    public function __construct()
    {
        parent::__construct('metric_engine.divide_by_zero', 'Division by zero is not allowed in metric calculations.');
    }
}
