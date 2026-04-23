<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Enums;

use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

enum AiHealth: string
{
    case DISABLED = 'disabled';
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case UNAVAILABLE = 'unavailable';

    public static function fromConfig(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw AiRuntimeContractException::unsupportedMode('AI health');
    }
}
