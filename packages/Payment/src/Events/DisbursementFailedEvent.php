<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when a disbursement fails.
 */
final class DisbursementFailedEvent extends DisbursementEvent
{
    public function __construct(
        string $disbursementId,
        string $tenantId,
        public readonly Money $amount,
        public readonly string $failureCode,
        public readonly string $failureMessage,
        public readonly bool $isRetryable,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($disbursementId, $tenantId, $occurredAt);
    }
}
