<?php

declare(strict_types=1);

namespace Nexus\Payment\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\ValueObjects\RecipientInfo;

/**
 * Event dispatched when a new disbursement is created.
 */
final class DisbursementCreatedEvent extends DisbursementEvent
{
    /**
     * @param array<string> $sourceDocumentIds
     */
    public function __construct(
        string $disbursementId,
        string $tenantId,
        public readonly string $referenceNumber,
        public readonly Money $amount,
        public readonly RecipientInfo $recipient,
        public readonly string $createdBy,
        public readonly array $sourceDocumentIds,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($disbursementId, $tenantId, $occurredAt);
    }
}
