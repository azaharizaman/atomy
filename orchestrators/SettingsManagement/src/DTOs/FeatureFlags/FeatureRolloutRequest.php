<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FeatureFlags;

/**
 * Targeting rules for feature rollout.
 * 
 * @param array<int, string> $userIds User IDs for user-list targeting
 * @param array<int, string> $ipRanges IP ranges for IP-based targeting (CIDR notation)
 * @param array<string, mixed> $customRules Custom rule definitions
 */
final class FeatureRolloutRequest
{
    /**
     * @param array<int, string>|null $userIds
     * @param array<int, string>|null $ipRanges
     * @param array<string, mixed>|null $customRules
     */
    public function __construct(
        public readonly string $flagId,
        public readonly string $flagKey,
        public readonly ?int $percentage = null,
        public readonly ?array $userIds = null,
        public readonly ?array $ipRanges = null,
        public readonly ?array $customRules = null,
        public readonly ?string $tenantId = null,
    ) {}
}
