<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class MissingInputException extends MetricEngineException
{
    public function __construct(string $inputName)
    {
        parent::__construct('metric_engine.missing_input', "Required metric input [{$inputName}] is missing.");
    }
}
