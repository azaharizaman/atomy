<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\PaymentMethod;

/**
 * Event dispatched when a payment method is selected.
 */
final readonly class PaymentMethodSelectedEvent
{
    public function __construct(
        public string $vendorId,
        public PaymentMethod $preferredMethod,
        public PaymentMethod $selectedMethod,
        public string $selectionReason,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
