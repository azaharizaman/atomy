<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use App\Repository\FeatureFlagRepository;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;

final readonly class FeatureFlagProvider implements FeatureFlagProviderInterface
{
    public function __construct(
        private FeatureFlagRepository $flagRepository
    ) {}

    public function getFlag(string $flagKey, string $tenantId): ?array
    {
        $flag = $this->flagRepository->find($flagKey, $tenantId);
        if (!$flag) return null;

        return [
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'strategy' => $flag->getStrategy()->value,
            'value' => $flag->getValue(),
            'metadata' => $flag->getMetadata(),
        ];
    }

    public function getAllFlags(string $tenantId): array
    {
        return $this->flagRepository->all($tenantId);
    }

    public function evaluateFlag(string $flagKey, string $tenantId, array $context = []): bool
    {
        $flag = $this->flagRepository->find($flagKey, $tenantId);
        return $flag ? $flag->isEnabled() : false;
    }

    public function flagExists(string $flagKey, string $tenantId): bool
    {
        return $this->flagRepository->find($flagKey, $tenantId) !== null;
    }

    public function getTargetingRules(string $flagKey, string $tenantId): array
    {
        return []; // Simple implementation
    }

    public function isFlagEnabled(string $flagKey, string $tenantId): bool
    {
        return $this->evaluateFlag($flagKey, $tenantId);
    }
}
