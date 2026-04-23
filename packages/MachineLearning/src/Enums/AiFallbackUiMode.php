<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

enum AiFallbackUiMode: string
{
    case HIDE_AI_CONTROLS = 'hide_ai_controls';
    case SHOW_UNAVAILABLE_MESSAGE = 'show_unavailable_message';
    case SHOW_MANUAL_CONTINUITY_BANNER = 'show_manual_continuity_banner';

    public static function fromConfig(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw AiRuntimeContractException::unsupportedMode('AI fallback UI mode');
    }
}
