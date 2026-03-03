<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface FeatureFlagPortInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function isEnabled(string $flag, array $context = []): bool;
}
