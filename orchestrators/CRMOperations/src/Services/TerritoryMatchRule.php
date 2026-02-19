<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\RoutingRuleInterface;

final readonly class TerritoryMatchRule implements RoutingRuleInterface
{
    public function __construct(
        private string $territory
    ) {}

    public function evaluate(array $leadData): bool
    {
        $leadTerritory = $leadData['territory'] ?? $leadData['region'] ?? null;
        return $leadTerritory === $this->territory;
    }

    public function getName(): string
    {
        return 'territory_match_' . $this->territory;
    }

    public function getPriority(): int
    {
        return 10;
    }
}
