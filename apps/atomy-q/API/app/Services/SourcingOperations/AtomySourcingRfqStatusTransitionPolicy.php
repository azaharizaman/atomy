<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Nexus\Sourcing\Contracts\RfqStatusTransitionPolicyInterface;
use Nexus\SourcingOperations\Contracts\SourcingRfqStatusTransitionPolicyInterface;

/**
 * Bridges sourcing-operations transitions to the sourcing domain policy.
 *
 * This adapter keeps Laravel orchestration dependent on the operations contract
 * while preserving the package-owned transition rules.
 */
final readonly class AtomySourcingRfqStatusTransitionPolicy implements SourcingRfqStatusTransitionPolicyInterface
{
    public function __construct(private RfqStatusTransitionPolicyInterface $policy)
    {
    }

    public function assertTransitionAllowed(string $fromStatus, string $toStatus): void
    {
        $this->policy->assertTransitionAllowed($fromStatus, $toStatus);
    }
}
