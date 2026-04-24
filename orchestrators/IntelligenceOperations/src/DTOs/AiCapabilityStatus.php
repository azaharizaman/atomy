<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiCapabilityStatus
{
    public const STATUS_AVAILABLE = AiStatusSchema::CAPABILITY_STATUS_AVAILABLE;
    public const STATUS_DEGRADED = AiStatusSchema::CAPABILITY_STATUS_DEGRADED;
    public const STATUS_DISABLED = AiStatusSchema::CAPABILITY_STATUS_DISABLED;
    public const STATUS_UNAVAILABLE = AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE;

    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        public string $featureKey,
        public string $capabilityGroup,
        public string $endpointGroup,
        public string $fallbackUiMode,
        public string $messageKey,
        public string $status,
        public bool $available,
        public array $reasonCodes,
        public bool $operatorCritical,
        public array $diagnostics = [],
    ) {
        $this->assertNonEmptyString($this->featureKey, 'feature key');
        $this->assertNonEmptyString($this->messageKey, 'message key');
        AiStatusSchema::assertCapabilityGroup($this->capabilityGroup);
        AiStatusSchema::assertEndpointGroup($this->endpointGroup);
        AiStatusSchema::assertFallbackUiMode($this->fallbackUiMode);
        $this->assertReasonCodes($this->reasonCodes);
        $this->assertDiagnostics($this->diagnostics);
        $this->assertStatusAvailabilityPair($this->status, $this->available);
    }

    public function toArray(): array
    {
        return [
            'feature_key' => $this->featureKey,
            'capability_group' => $this->capabilityGroup,
            'endpoint_group' => $this->endpointGroup,
            'status' => $this->status,
            'available' => $this->available,
            'fallback_ui_mode' => $this->fallbackUiMode,
            'message_key' => $this->messageKey,
            'reason_codes' => AiStatusSchema::sanitizeReasonCodes($this->reasonCodes),
            'operator_critical' => $this->operatorCritical,
            'diagnostics' => AiStatusSchema::sanitizeDiagnostics($this->diagnostics),
        ];
    }

    private function assertNonEmptyString(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw AiStatusContractException::invalidValue(sprintf('AI capability status %s', $label));
        }
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function assertReasonCodes(array $reasonCodes): void
    {
        foreach ($reasonCodes as $reasonCode) {
            if (!is_string($reasonCode) || trim($reasonCode) === '') {
                throw AiStatusContractException::invalidValue('AI capability status reason codes');
            }
        }
    }

    /**
     * @param array<string, scalar|null> $diagnostics
     */
    private function assertDiagnostics(array $diagnostics): void
    {
        foreach ($diagnostics as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw AiStatusContractException::invalidValue('AI capability status diagnostics');
            }

            if (!is_scalar($value) && $value !== null) {
                throw AiStatusContractException::invalidValue('AI capability status diagnostics');
            }
        }
    }

    private function assertStatusAvailabilityPair(string $status, bool $available): void
    {
        $expectedAvailability = match ($status) {
            self::STATUS_AVAILABLE => true,
            self::STATUS_DEGRADED, self::STATUS_DISABLED, self::STATUS_UNAVAILABLE => false,
            default => throw AiStatusContractException::invalidValue('AI capability status'),
        };

        if ($expectedAvailability !== $available) {
            throw AiStatusContractException::invalidValue('AI capability status availability');
        }
    }
}
