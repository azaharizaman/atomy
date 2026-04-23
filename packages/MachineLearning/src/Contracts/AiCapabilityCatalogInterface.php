<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\AiCapabilityDefinition;

interface AiCapabilityCatalogInterface
{
    /**
     * @return array<int, AiCapabilityDefinition>
     */
    public function all(): array;

    public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition;
}
