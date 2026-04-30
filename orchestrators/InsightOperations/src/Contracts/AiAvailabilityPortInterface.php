<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface AiAvailabilityPortInterface
{
    public function isFeatureAvailable(string $featureKey): bool;

    /**
     * @return list<string>
     */
    public function reasonCodes(string $featureKey): array;
}
