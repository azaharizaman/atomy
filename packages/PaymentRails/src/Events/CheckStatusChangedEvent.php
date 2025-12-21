<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\CheckStatus;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when a check status changes.
 */
final class CheckStatusChangedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $checkNumber
     * @param CheckStatus $previousStatus
     * @param CheckStatus $newStatus
     * @param string|null $reason
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $checkNumber,
        public readonly CheckStatus $previousStatus,
        public readonly CheckStatus $newStatus,
        public readonly ?string $reason,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::CHECK, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'check.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'check_number' => $this->checkNumber,
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
            'reason' => $this->reason,
        ]);
    }
}
