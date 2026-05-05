<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Exceptions;

final class InsufficientDataException extends MetricEngineException
{
    public function __construct(string $message)
    {
        parent::__construct('metric_engine.insufficient_data', $message);
    }
}
