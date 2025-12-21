<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\CheckStatus;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when a check is issued.
 */
final class CheckIssuedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $checkNumber
     * @param int $amountCents
     * @param string $currency
     * @param string $payeeName
     * @param CheckStatus $status
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $checkNumber,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly string $payeeName,
        public readonly CheckStatus $status,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::CHECK, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'check.issued';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'check_number' => $this->checkNumber,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'payee_name' => $this->payeeName,
            'status' => $this->status->value,
        ]);
    }
}
