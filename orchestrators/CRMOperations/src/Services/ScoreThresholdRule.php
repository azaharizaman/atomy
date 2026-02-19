<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\RoutingRuleInterface;

final readonly class ScoreThresholdRule implements RoutingRuleInterface
{
    public function __construct(
        private int $threshold,
        private string $operator = '>='
    ) {}

    public function evaluate(array $leadData): bool
    {
        $score = $leadData['score'] ?? 0;
        
        return match ($this->operator) {
            '>=' => $score >= $this->threshold,
            '>' => $score > $this->threshold,
            '<=' => $score <= $this->threshold,
            '<' => $score < $this->threshold,
            '==' => $score === $this->threshold,
            default => false,
        };
    }

    public function getName(): string
    {
        return 'score_' . $this->operator . '_' . $this->threshold;
    }

    public function getPriority(): int
    {
        return 5;
    }
}
