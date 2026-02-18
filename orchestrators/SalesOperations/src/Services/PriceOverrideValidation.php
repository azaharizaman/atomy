<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class PriceOverrideValidation
{
    public function __construct(
        public float $originalPrice,
        public float $overridePrice,
        public float $discountPercent,
        public bool $isValid,
        public bool $requiresApproval,
        public ?string $approverId,
        public string $overrideReason,
        public array $warnings,
    ) {}

    public function isApproved(): bool
    {
        return $this->isValid && (!$this->requiresApproval || $this->approverId !== null);
    }
}
