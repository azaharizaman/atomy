<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Context for evaluating feature flags.
 * 
 * @param array<string, mixed> $context Additional context for evaluation
 */
final class FlagEvaluationRequest
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly array $flagKeys,
        public readonly ?string $userId = null,
        public readonly ?string $ipAddress = null,
        public readonly array $context = [],
    ) {}
}
