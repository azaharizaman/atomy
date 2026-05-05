<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

interface WindowResolverInterface
{
    public function resolve(MetricSeries $series, TimeWindow $window): MetricSeries;
}
