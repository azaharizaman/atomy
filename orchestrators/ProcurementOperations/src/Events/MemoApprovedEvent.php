<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\MemoStatus;

/**
 * Event published when a memo is approved.
 */
final readonly class MemoApprovedEvent
{
    public function __construct(
        public string $tenantId,
        public string $memoId,
        public string $memoNumber,
        public string $vendorId,
        public Money $amount,
        public string $approvedBy,
        public \DateTimeImmutable $approvedAt,
        public ?string $notes = null,
    ) {}
}
