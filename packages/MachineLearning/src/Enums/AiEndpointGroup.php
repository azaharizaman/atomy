<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

enum AiEndpointGroup: string
{
    case DOCUMENT = 'document';
    case NORMALIZATION = 'normalization';
    case SOURCING_RECOMMENDATION = 'sourcing_recommendation';
    case COMPARISON_AWARD = 'comparison_award';
    case INSIGHT = 'insight';
    case GOVERNANCE = 'governance';

    public static function fromConfig(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw AiRuntimeContractException::unsupportedMode('AI endpoint group');
    }
}
