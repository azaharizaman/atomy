<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\MissingInputException;

final readonly class MetricEvaluationBatchResult
{
    /** @param array<string, MetricEvaluationOutcome> $outcomes */
    public function __construct(
        private array $outcomes
    ) {}

    public function get(string $formulaIdentifier): MetricEvaluationOutcome
    {
        if (! isset($this->outcomes[$formulaIdentifier])) {
            throw new MissingInputException($formulaIdentifier);
        }

        return $this->outcomes[$formulaIdentifier];
    }

    /** @return array<string, MetricEvaluationOutcome> */
    public function all(): array
    {
        return $this->outcomes;
    }
}
