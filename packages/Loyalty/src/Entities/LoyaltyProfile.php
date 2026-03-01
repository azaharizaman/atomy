<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Entities;

use Nexus\Loyalty\ValueObjects\PointBalance;
use Nexus\Loyalty\ValueObjects\TierStatus;

/**
 * The root aggregate model for a member's loyalty status within a tenant.
 * Requirements: BUS-LOY-001, SEC-LOY-001, SEC-LOY-003, USA-LOY-002
 */
final readonly class LoyaltyProfile
{
    /**
     * @param string $memberId Unique identifier linking to Identity UUID.
     * @param string $tenantId The tenant this profile belongs to (Multi-tenant isolation).
     * @param PointBalance $balance The current point balance aggregate.
     * @param TierStatus $tier Current tier status and benefits.
     * @param array<string, mixed> $metadata Additional attributes (e.g., brand_affiliations).
     */
    public function __construct(
        public string $memberId,
        public string $tenantId,
        public PointBalance $balance,
        public TierStatus $tier,
        public array $metadata = []
    ) {
    }
}
