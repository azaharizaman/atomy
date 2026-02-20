<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\RoutingRuleInterface;

final readonly class IndustryMatchRule implements RoutingRuleInterface
{
    public function __construct(
        readonly array $industries
    ) {}

    public function evaluate(array $leadData): bool
    {
        $industry = $leadData['industry'] ?? null;
        return in_array($industry, $this->industries, true);
    }

    public function getName(): string
    {
        return 'industry_match_' . implode('_', $this->industries);
    }

    public function getPriority(): int
    {
        return 7;
    }
}
