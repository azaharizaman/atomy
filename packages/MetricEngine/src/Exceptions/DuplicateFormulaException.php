<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

class DuplicateFormulaException extends MetricEngineException
{
    public function __construct(string $identifier)
    {
        parent::__construct('metric_engine.duplicate_formula', "Formula [{$identifier}] is already registered.");
    }
}
