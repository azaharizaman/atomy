<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;

interface MilestoneBillingServiceInterface
{
    public function processMilestoneCompletion(
        string $tenantId,
        MilestoneDTO $milestone,
        Money $amount
    ): string;
}
