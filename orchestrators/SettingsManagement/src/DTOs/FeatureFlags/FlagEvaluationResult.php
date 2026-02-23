<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Result DTO for feature flag evaluation.
 * 
 * @param array<string, bool> $results Map of flag keys to boolean results
 */
final class FlagEvaluationResult
{
    /**
     * @param array<string, bool> $results
     */
    public function __construct(
        public readonly array $results,
        public readonly bool $success = true,
        public readonly ?string $error = null,
    ) {}

    public static function success(array $results): self
    {
        return new self(results: $results, success: true);
    }

    public static function failure(string $error): self
    {
        return new self(results: [], success: false, error: $error);
    }

    public function isEnabled(string $flagKey): bool
    {
        return $this->results[$flagKey] ?? false;
    }
}
