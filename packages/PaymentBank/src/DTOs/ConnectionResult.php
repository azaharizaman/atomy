<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class ConnectionResult
{
    public function __construct(
        public string $accessToken,
        public string $itemId,
        public ?string $requestId = null,
        public array $metadata = []
    ) {}
}
