<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;

final readonly class FeatureFlagProvider implements FeatureFlagProviderInterface
{
    public function __construct(
        private FlagRepositoryInterface $flagRepository
    ) {}

    public function getFlag(string $flagKey, string $tenantId): ?array
    {
        $flag = $this->flagRepository->findByName($flagKey, $tenantId);
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
        $flags = $this->flagRepository->all($tenantId);
        
        return array_map(fn($flag) => [
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'strategy' => $flag->getStrategy()->value,
            'value' => $flag->getValue(),
            'metadata' => $flag->getMetadata(),
        ], $flags);
    }

    public function evaluateFlag(string $flagKey, string $tenantId, array $context = []): bool
    {
        $flag = $this->flagRepository->findByName($flagKey, $tenantId);
        return $flag ? $flag->isEnabled() : false;
    }

    public function flagExists(string $flagKey, string $tenantId): bool
    {
        return $this->flagRepository->findByName($flagKey, $tenantId) !== null;
    }

    public function getTargetingRules(string $flagKey, string $tenantId): array
    {
        return [];
    }

    public function isFlagEnabled(string $flagKey, string $tenantId): bool
    {
        return $this->evaluateFlag($flagKey, $tenantId);
    }
}
