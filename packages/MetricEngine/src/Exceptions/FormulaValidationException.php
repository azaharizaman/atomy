<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class FormulaValidationException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.formula_validation', $message);
    }
}
