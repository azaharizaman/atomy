<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class ProviderContractVerificationResult
{
    /**
     * @param list<string> $reasonCodes
     */
    public function __construct(
        public string $endpointGroup,
        public string $severity,
        public bool $verified,
        public array $reasonCodes,
        public string $message,
    ) {
    }

    /**
     * @return array{
     *     endpoint_group: string,
     *     severity: string,
     *     verified: bool,
     *     reason_codes: list<string>,
     *     message: string
     * }
     */
    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup,
            'severity' => $this->severity,
            'verified' => $this->verified,
            'reason_codes' => $this->reasonCodes,
            'message' => $this->message,
        ];
    }
}
