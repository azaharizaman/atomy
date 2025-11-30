<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when usage quota is exceeded
 */
class QuotaExceededException extends IntelligenceException
{
    public static function forTenant(string $tenantId, string $quotaType): self
    {
        return new self(
            "Quota exceeded for tenant '{$tenantId}': {$quotaType}. " .
            "Please contact support to increase your quota."
        );
    }

    public static function forModel(string $modelName, int $monthlyLimit): self
    {
        return new self(
            "Monthly quota of {$monthlyLimit} requests exceeded for model '{$modelName}'. " .
            "Quota will reset at the beginning of next month."
        );
    }
}
