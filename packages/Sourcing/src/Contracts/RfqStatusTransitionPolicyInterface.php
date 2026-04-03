<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Contracts;

interface RfqStatusTransitionPolicyInterface
{
    public function canTransition(string $fromStatus, string $toStatus): bool;

    /**
     * @return array<string>
     */
    public function allowedTransitions(string $fromStatus): array;

    /**
     * @throws \Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException
     */
    public function assertTransitionAllowed(string $fromStatus, string $toStatus): void;
}
