<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Order;

use Nexus\SalesOperations\Rules\RuleResult;

final readonly class OrderValidationResult
{
    /**
     * @param bool $passed Whether all rules passed
     * @param array<string, RuleResult> $results Individual rule results
     */
    public function __construct(
        public bool $passed,
        public array $results
    ) {}

    public function getFailedRules(): array
    {
        return array_filter(
            $this->results,
            fn(RuleResult $r) => $r->failed()
        );
    }

    public function getFailureMessages(): array
    {
        return array_map(
            fn(RuleResult $r) => $r->message,
            $this->getFailedRules()
        );
    }

    public function getFirstFailure(): ?RuleResult
    {
        foreach ($this->results as $result) {
            if ($result->failed()) {
                return $result;
            }
        }

        return null;
    }
}
