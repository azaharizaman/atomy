<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

interface SourcingRfqStatusTransitionPolicyInterface
{
    /**
     * @throws \Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException
     */
    public function assertTransitionAllowed(string $fromStatus, string $toStatus): void;
}
