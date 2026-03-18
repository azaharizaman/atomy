<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use DomainException;
use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;

final readonly class AtomyMilestoneBillingService implements MilestoneBillingServiceInterface
{
    public function processMilestoneCompletion(string $tenantId, MilestoneDTO $milestone, Money $amount): string
    {
        throw new DomainException(sprintf(
            'Milestone billing not implemented (tenant=%s, milestone=%s, amount=%s).',
            $tenantId,
            $milestone->id,
            method_exists($amount, '__toString') ? (string) $amount : 'unknown'
        ));
    }
}
