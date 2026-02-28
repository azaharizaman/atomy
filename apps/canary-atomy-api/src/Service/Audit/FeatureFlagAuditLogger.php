<?php

declare(strict_types=1);

namespace App\Service\Audit;

use Nexus\FeatureFlags\Contracts\FlagAuditChangeInterface;
use Nexus\FeatureFlags\Enums\AuditAction;
use Psr\Log\LoggerInterface;

/**
 * Feature Flag Audit Logger.
 * 
 * Simple implementation that logs changes to the system logger.
 * In a real app, this would use Nexus\AuditLogger to store in DB.
 */
final readonly class FeatureFlagAuditLogger implements FlagAuditChangeInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function recordChange(
        string $flagName,
        AuditAction $action,
        ?string $userId,
        ?array $before,
        ?array $after,
        array $metadata = []
    ): void {
        $this->logger->info(sprintf('Feature flag "%s" %s by user "%s"', $flagName, $action->value, $userId ?? 'system'), [
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
        ]);
    }

    public function recordBatchChange(
        AuditAction $action,
        ?string $userId,
        array $changes,
        array $metadata = []
    ): void {
        foreach ($changes as $flagName => $change) {
            $this->recordChange($flagName, $action, $userId, $change['before'], $change['after'], $metadata);
        }
    }
}
