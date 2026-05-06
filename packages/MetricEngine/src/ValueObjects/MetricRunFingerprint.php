<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricRunFingerprint
{
    public function __construct(
        public string $algorithm,
        public string $hash
    ) {}
}
