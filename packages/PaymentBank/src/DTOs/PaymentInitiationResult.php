<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class PaymentInitiationResult
{
    public function __construct(
        public string $paymentId,
        public string $status,
        public ?string $redirectUrl = null,
        public array $metadata = []
    ) {}
}
