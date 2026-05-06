<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\MetricResultStatus;

final readonly class MetricEvaluationOutcome
{
    public function __construct(
        public string $formulaIdentifier,
        public MetricResultStatus $status,
        public ?MetricResult $result = null,
        public ?string $reasonCode = null,
        public ?string $message = null,
        public ?MetricAuditTrace $auditTrace = null
    ) {}

    public static function available(MetricResult $result, ?MetricAuditTrace $auditTrace = null): self
    {
        return new self($result->formulaIdentifier(), MetricResultStatus::AVAILABLE, $result, null, null, $auditTrace);
    }

    public static function unavailable(string $formulaIdentifier, MetricResultStatus $status, \Throwable $error): self
    {
        return new self(
            formulaIdentifier: $formulaIdentifier,
            status: $status,
            result: null,
            reasonCode: $error instanceof \Nexus\MetricEngine\Exceptions\MetricEngineException ? $error->errorCode() : 'unexpected_error',
            message: $error->getMessage()
        );
    }

    public static function dependencyUnavailable(string $formulaIdentifier, string $dependencyIdentifier): self
    {
        return new self(
            formulaIdentifier: $formulaIdentifier,
            status: MetricResultStatus::NOT_AVAILABLE,
            result: null,
            reasonCode: 'dependency_not_available',
            message: "Formula [{$formulaIdentifier}] depends on unavailable formula [{$dependencyIdentifier}]."
        );
    }
}
