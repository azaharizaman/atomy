<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Services;

use Nexus\Sourcing\Contracts\RfqStatusTransitionPolicyInterface;
use Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException;

final readonly class RfqStatusTransitionPolicy implements RfqStatusTransitionPolicyInterface
{
    /**
     * @var array<string, array<string>>
     */
    private const TRANSITIONS = [
        'draft' => ['published', 'cancelled'],
        'published' => ['closed', 'cancelled'],
        'closed' => ['awarded', 'cancelled'],
        'awarded' => [],
        'cancelled' => [],
    ];

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        $from = $this->normalizeStatus($fromStatus);
        $to = $this->normalizeStatus($toStatus);

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function allowedTransitions(string $fromStatus): array
    {
        $from = $this->normalizeStatus($fromStatus);

        return self::TRANSITIONS[$from] ?? [];
    }

    public function assertTransitionAllowed(string $fromStatus, string $toStatus): void
    {
        if ($this->canTransition($fromStatus, $toStatus)) {
            return;
        }

        throw InvalidRfqStatusTransitionException::fromStatuses($fromStatus, $toStatus);
    }

    private function normalizeStatus(string $status): string
    {
        return strtolower(trim($status));
    }
}
