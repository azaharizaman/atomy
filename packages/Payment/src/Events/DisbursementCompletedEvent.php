<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a disbursement is successfully completed.
 */
final class DisbursementCompletedEvent extends DisbursementEvent
{
    public function __construct(
        string $disbursementId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $paymentTransactionId,
        public readonly \DateTimeImmutable $completedAt,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($disbursementId, $tenantId, $occurredAt);
    }
}
