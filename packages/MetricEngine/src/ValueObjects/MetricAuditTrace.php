<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class MetricAuditTrace
{
    /**
     * @param list<mixed> $operands
     * @param list<mixed> $resolvedOperands
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $dependencyResults
     * @param list<array<string, mixed>> $excludedValues
     */
    public function __construct(
        public string $formulaIdentifier,
        public string $operation,
        public array $operands,
        public array $resolvedOperands,
        public array $inputs,
        public array $dependencyResults,
        public array $excludedValues,
        public mixed $resultValue,
        public string $status,
        public ?string $reasonCode = null,
        public ?string $message = null
    ) {}
}
