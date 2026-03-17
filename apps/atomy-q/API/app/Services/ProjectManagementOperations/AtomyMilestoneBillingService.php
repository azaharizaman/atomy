<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;

final readonly class AtomyMilestoneBillingService implements MilestoneBillingServiceInterface
{
    public function processMilestoneCompletion(string $tenantId, MilestoneDTO $milestone, Money $amount): string
    {
        return 'stub-' . $milestone->id;
    }
}
