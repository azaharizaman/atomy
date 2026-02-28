<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;

final readonly class FeatureFlagProvider implements FeatureFlagProviderInterface
{
    public function __construct(
        private FlagRepositoryInterface $flagRepository,
        private FlagEvaluatorInterface $flagEvaluator
    ) {}

    public function getFlag(string $flagKey, string $tenantId): ?array
    {
        $flag = $this->flagRepository->findByName($flagKey, $tenantId);
        if (!$flag) return null;

        return $this->transformFlag($flag);
    }

    public function getAllFlags(string $tenantId): array
    {
        $flags = $this->flagRepository->all($tenantId);
        
        return array_map(fn($flag) => $this->transformFlag($flag), $flags);
    }

    public function evaluateFlag(string $flagKey, string $tenantId, array $context = []): bool
    {
        $flag = $this->flagRepository->findByName($flagKey, $tenantId);
        if (!$flag) {
            return false;
        }

        $evalContext = EvaluationContext::fromArray(array_merge($context, ['tenantId' => $tenantId]));
        
        return $this->flagEvaluator->evaluate($flag, $evalContext);
    }

    public function flagExists(string $flagKey, string $tenantId): bool
    {
        return $this->flagRepository->findByName($flagKey, $tenantId) !== null;
    }

    public function getTargetingRules(string $flagKey, string $tenantId): array
    {
        $flag = $this->flagRepository->findByName($flagKey, $tenantId);
        // Assuming metadata or strategy value contains rules if applicable
        return $flag ? ($flag->getMetadata() ?: []) : [];
    }

    public function isFlagEnabled(string $flagKey, string $tenantId): bool
    {
        return $this->evaluateFlag($flagKey, $tenantId);
    }

    private function transformFlag(FlagDefinitionInterface $flag): array
    {
        return [
            'name' => $flag->getName(),
            'enabled' => $flag->isEnabled(),
            'strategy' => $flag->getStrategy()->value,
            'value' => $flag->getValue(),
            'metadata' => $flag->getMetadata(),
        ];
    }
}
