<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a disbursement is approved.
 */
final class DisbursementApprovedEvent extends DisbursementEvent
{
    public function __construct(
        string $disbursementId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $approvedBy,
        public readonly ?string $notes,
        public readonly \DateTimeImmutable $approvedAt,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($disbursementId, $tenantId, $occurredAt);
    }
}
