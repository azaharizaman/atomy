<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use Nexus\Loyalty\Contracts\AdjustmentServiceInterface;
use Nexus\Loyalty\Models\LoyaltyProfile;
use Psr\Log\LoggerInterface;

/**
 * Service for manual point adjustments with mandatory auditing.
 * Requirement: FUN-LOY-401
 */
final readonly class AdjustmentService implements AdjustmentServiceInterface
{
    /**
     * @param LoggerInterface $auditLogger PSR-3 Logger for audit trails.
     */
    public function __construct(
        private LoggerInterface $auditLogger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function adjustPoints(
        LoyaltyProfile $profile,
        int $points,
        string $reasonCode,
        string $adminId,
        array $metadata = []
    ): int {
        // Log the adjustment for audit trail
        $this->auditLogger->info("Manual point adjustment performed.", [
            'member_id' => $profile->memberId,
            'tenant_id' => $profile->tenantId,
            'points_delta' => $points,
            'reason_code' => $reasonCode,
            'admin_id' => $adminId,
            'previous_balance' => $profile->balance->totalAvailable,
            'new_balance' => $profile->balance->totalAvailable + $points,
            'metadata' => $metadata,
        ]);

        return $profile->balance->totalAvailable + $points;
    }
}
