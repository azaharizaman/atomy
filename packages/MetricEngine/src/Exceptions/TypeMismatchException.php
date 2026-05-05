<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class TypeMismatchException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.type_mismatch', $message);
    }
}
