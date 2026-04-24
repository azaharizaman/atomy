<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

enum AiMode: string
{
    case OFF = 'off';
    case PROVIDER = 'provider';
    case DETERMINISTIC = 'deterministic';

    public static function fromConfig(string $value): self
    {
        $normalized = strtolower(trim($value));

        if ($normalized === 'llm') {
            return self::PROVIDER;
        }

        return self::tryFrom($normalized) ?? throw AiRuntimeContractException::unsupportedMode($normalized);
    }
}
