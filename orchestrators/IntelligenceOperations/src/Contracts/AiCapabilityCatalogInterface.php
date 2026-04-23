<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

use Nexus\IntelligenceOperations\DTOs\AiCapabilityDefinition;

interface AiCapabilityCatalogInterface
{
    /**
     * @return array<int, AiCapabilityDefinition>
     */
    public function all(): array;

    public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition;
}
