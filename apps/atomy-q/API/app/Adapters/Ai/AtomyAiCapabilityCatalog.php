<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\Contracts\AiCapabilityCatalogInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityDefinition;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class AtomyAiCapabilityCatalog implements AiCapabilityCatalogInterface
{
    /**
     * @return array<int, AiCapabilityDefinition>
     */
    public function all(): array
    {
        return [
            new AiCapabilityDefinition(
                featureKey: 'document_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.document_intelligence.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
            new AiCapabilityDefinition(
                featureKey: 'normalization_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: false,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                degradationMessageKey: 'ai.normalization_intelligence.unavailable',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION,
            ),
            new AiCapabilityDefinition(
                featureKey: 'sourcing_recommendation_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.sourcing_recommendation_intelligence.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
            ),
            new AiCapabilityDefinition(
                featureKey: 'comparison_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.comparison_intelligence.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
            ),
            new AiCapabilityDefinition(
                featureKey: 'award_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.award_intelligence.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
            ),
            new AiCapabilityDefinition(
                featureKey: 'insight_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.insight_intelligence.degraded',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
            ),
            new AiCapabilityDefinition(
                featureKey: 'governance_intelligence',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.governance_intelligence.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            ),
        ];
    }

    public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition
    {
        foreach ($this->all() as $definition) {
            if ($definition->featureKey === $featureKey) {
                return $definition;
            }
        }

        return null;
    }
}
