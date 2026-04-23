<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

use DateTimeImmutable;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

interface AiStatusCoordinatorInterface
{
    /**
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     */
    public function snapshot(
        string $mode,
        array $endpointGroupHealthSnapshots,
        ?DateTimeImmutable $generatedAt = null,
    ): AiStatusSnapshot;
}
