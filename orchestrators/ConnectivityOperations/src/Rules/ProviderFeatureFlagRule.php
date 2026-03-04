<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Rules;

use Nexus\ConnectivityOperations\Contracts\FeatureFlagPortInterface;

final readonly class ProviderFeatureFlagRule
{
    public function __construct(private FeatureFlagPortInterface $featureFlagPort) {}

    /**
     * @param array<string, mixed> $context
     */
    public function assertEnabled(string $providerId, array $context = []): void
    {
        if (!$this->featureFlagPort->isEnabled('integration.' . $providerId, $context)) {
            throw new \RuntimeException(sprintf('Integration [%s] is currently disabled.', $providerId));
        }
    }
}
