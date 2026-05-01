<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderCheckFinding
{
    public function __construct(
        public string $severity,
        public string $area,
        public string $message,
        public ?string $endpointGroup = null,
        public ?string $reasonCode = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'area' => $this->area,
            'message' => $this->message,
            'endpoint_group' => $this->endpointGroup,
            'reason_code' => $this->reasonCode,
        ];
    }
}
