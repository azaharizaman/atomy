<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

final readonly class Institution
{
    public function __construct(
        public string $id,
        public string $name,
        public array $products,
        public array $countryCodes,
        public ?string $logo = null,
        public ?string $primaryColor = null,
        public ?string $url = null
    ) {}
}
