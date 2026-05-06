<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\MetricResultStatus;
use Nexus\MetricEngine\Exceptions\DivideByZeroMetricException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\MissingInputException;

class MetricStatusInferenceService
{
    public function infer(\Throwable $error): MetricResultStatus
    {
        return match (true) {
            $error instanceof InsufficientDataException => MetricResultStatus::NO_DATA,
            $error instanceof MissingInputException => MetricResultStatus::NOT_AVAILABLE,
            $error instanceof DivideByZeroMetricException => MetricResultStatus::NOT_AVAILABLE,
            default => MetricResultStatus::ERROR,
        };
    }
}
