<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use ArrayObject;
use Nexus\IntelligenceOperations\Contracts\AiCapabilityCatalogInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityDefinition;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class AtomyAiCapabilityCatalog implements AiCapabilityCatalogInterface
{
    /**
     * @var ArrayObject<int, AiCapabilityDefinition>
     */
    private ArrayObject $definitions;

    /**
     * @var ArrayObject<string, AiCapabilityDefinition>
     */
    private ArrayObject $definitionsByFeatureKey;

    public function __construct()
    {
        $this->definitions = new ArrayObject();
        $this->definitionsByFeatureKey = new ArrayObject();
    }

    /**
     * @return array<int, AiCapabilityDefinition>
     */
    public function all(): array
    {
        if ($this->definitions->count() === 0) {
            $definitions = [
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
                    featureKey: 'quote_document_extraction',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                    requiresAi: true,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    degradationMessageKey: 'ai.quote_document_extraction.manual_continuity',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'quote_source_line_manual_edit',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                    requiresAi: false,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
                    degradationMessageKey: 'ai.quote_source_line_manual_edit.available',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'quote_reparse_extraction',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                    requiresAi: true,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    degradationMessageKey: 'ai.quote_reparse_extraction.manual_continuity',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'normalization_intelligence',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                    requiresAi: true,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    degradationMessageKey: 'ai.normalization_intelligence.degraded',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'normalization_suggestions',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                    requiresAi: true,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                    degradationMessageKey: 'ai.normalization_suggestions.manual_continuity',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'normalization_manual_mapping',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                    requiresAi: false,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
                    degradationMessageKey: 'ai.normalization_manual_mapping.available',
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
                    featureKey: 'vendor_ai_ranking',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                    requiresAi: true,
                    hasManualFallback: false,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                    degradationMessageKey: 'ai.vendor_ai_ranking.unavailable',
                    operatorCritical: true,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
                ),
                new AiCapabilityDefinition(
                    featureKey: 'vendor_manual_selection',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                    requiresAi: false,
                    hasManualFallback: true,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
                    degradationMessageKey: 'ai.vendor_manual_selection.available',
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

            foreach ($definitions as $definition) {
                $this->definitions->append($definition);
                $this->definitionsByFeatureKey[$definition->featureKey] = $definition;
            }
        }

        /** @var array<int, AiCapabilityDefinition> $resolvedDefinitions */
        $resolvedDefinitions = array_values($this->definitions->getArrayCopy());

        return $resolvedDefinitions;
    }

    public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition
    {
        $this->all();

        $definition = $this->definitionsByFeatureKey[$featureKey] ?? null;

        return $definition instanceof AiCapabilityDefinition ? $definition : null;
    }
}
