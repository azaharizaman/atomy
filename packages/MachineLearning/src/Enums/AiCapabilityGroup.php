<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

enum AiCapabilityGroup: string
{
    case DOCUMENT_INTELLIGENCE = 'document_intelligence';
    case NORMALIZATION_INTELLIGENCE = 'normalization_intelligence';
    case SOURCING_RECOMMENDATION_INTELLIGENCE = 'sourcing_recommendation_intelligence';
    case COMPARISON_INTELLIGENCE = 'comparison_intelligence';
    case AWARD_INTELLIGENCE = 'award_intelligence';
    case INSIGHT_INTELLIGENCE = 'insight_intelligence';
    case GOVERNANCE_INTELLIGENCE = 'governance_intelligence';

    public static function fromConfig(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw AiRuntimeContractException::unsupportedMode('AI capability group');
    }
}
