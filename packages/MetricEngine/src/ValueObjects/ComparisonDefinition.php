<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\ComparisonType;

final readonly class ComparisonDefinition
{
    public function __construct(
        public ComparisonType $type
    ) {}
}
