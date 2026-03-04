<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\FeatureFlagPortInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;

final readonly class FeatureFlagPortAdapter implements FeatureFlagPortInterface
{
    public function __construct(private FeatureFlagManagerInterface $manager) {}

    public function isEnabled(string $flag, array $context = []): bool
    {
        return $this->manager->isEnabled($flag, $context);
    }
}
