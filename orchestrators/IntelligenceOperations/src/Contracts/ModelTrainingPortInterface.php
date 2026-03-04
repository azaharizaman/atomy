<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Contracts;

interface ModelTrainingPortInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function queueRetraining(string $modelId, array $context): string;
}
