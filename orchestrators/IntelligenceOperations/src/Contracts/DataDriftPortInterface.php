<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

interface DataDriftPortInterface
{
    public function calculateDriftScore(string $modelId): float;
}
