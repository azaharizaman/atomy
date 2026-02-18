<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Enums;

enum PricingTier: string
{
    case TIER_1_SMB = 'tier_1_smb';
    case TIER_2_MID_MARKET = 'tier_2_mid_market';
    case TIER_3_ENTERPRISE = 'tier_3_enterprise';

    public function hasCreditLimitCheck(): bool
    {
        return $this !== self::TIER_1_SMB;
    }

    public function hasMultiWarehouse(): bool
    {
        return $this !== self::TIER_1_SMB;
    }

    public function hasCommissionTracking(): bool
    {
        return $this !== self::TIER_1_SMB;
    }

    public function hasRevenueRecognition(): bool
    {
        return $this === self::TIER_3_ENTERPRISE;
    }

    public function hasApprovalWorkflow(): bool
    {
        return $this === self::TIER_3_ENTERPRISE;
    }
}
