<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a disbursement is rejected.
 */
final class DisbursementRejectedEvent extends DisbursementEvent
{
    public function __construct(
        string $disbursementId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $rejectedBy,
        public readonly string $reason,
        public readonly \DateTimeImmutable $rejectedAt,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($disbursementId, $tenantId, $occurredAt);
    }
}
