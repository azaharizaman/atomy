<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\AiArtifactDto;

interface GovernanceNarrativePortInterface
{
    /**
     * @param array<string, mixed> $facts
     */
    public function generate(string $featureKey, string $tenantId, string $subjectType, string $actorId, array $facts): AiArtifactDto;
}
