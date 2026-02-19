<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\RoutingRuleInterface;

final readonly class SourceMatchRule implements RoutingRuleInterface
{
    public function __construct(
        readonly array $sources
    ) {}

    public function evaluate(array $leadData): bool
    {
        $source = $leadData['source'] ?? null;
        return in_array($source, $this->sources, true);
    }

    public function getName(): string
    {
        return 'source_match_' . implode('_', $this->sources);
    }

    public function getPriority(): int
    {
        return 8;
    }
}
