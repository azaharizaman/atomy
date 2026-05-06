<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricEvaluationOptions
{
    public function __construct(
        public bool $includeAuditTrace = false
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function withAuditTrace(): self
    {
        return new self(includeAuditTrace: true);
    }
}
