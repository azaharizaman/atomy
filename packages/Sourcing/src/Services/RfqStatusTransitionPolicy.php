<?php

declare(strict_types=1);

namespace Nexus\Sourcing\Services;

use Nexus\Sourcing\Contracts\RfqStatusTransitionPolicyInterface;
use Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException;
use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\Sourcing\ValueObjects\RfqStatus;

final readonly class RfqStatusTransitionPolicy implements RfqStatusTransitionPolicyInterface
{
    /**
     * @var array<string, array<string>>
     */
    private const TRANSITIONS = [
        RfqStatus::DRAFT => [RfqStatus::PUBLISHED, RfqStatus::CANCELLED],
        RfqStatus::PUBLISHED => [RfqStatus::CLOSED, RfqStatus::CANCELLED],
        RfqStatus::CLOSED => [RfqStatus::AWARDED, RfqStatus::CANCELLED],
        RfqStatus::AWARDED => [],
        RfqStatus::CANCELLED => [],
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

        return self::TRANSITIONS[$from];
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
        $normalized = strtolower(trim($status));

        if ($normalized === '' || !array_key_exists($normalized, self::TRANSITIONS)) {
            throw RfqLifecyclePreconditionException::forReason(sprintf(
                'Unknown RFQ status "%s".',
                trim($status),
            ));
        }

        return $normalized;
    }
}
