<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class InvalidNumericValueException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.invalid_numeric_value', $message);
    }
}
