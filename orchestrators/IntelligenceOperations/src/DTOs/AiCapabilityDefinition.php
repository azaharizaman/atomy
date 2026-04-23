<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiCapabilityDefinition
{
    public function __construct(
        public string $featureKey,
        public string $capabilityGroup,
        public bool $requiresAi,
        public bool $hasManualFallback,
        public string $fallbackUiMode,
        public string $degradationMessageKey,
        public bool $operatorCritical,
        public string $endpointGroup,
    ) {
        $this->assertNonEmptyString($this->featureKey, 'feature key');
        $this->assertNonEmptyString($this->degradationMessageKey, 'degradation message key');
        AiStatusSchema::assertCapabilityGroup($this->capabilityGroup);
        AiStatusSchema::assertFallbackUiMode($this->fallbackUiMode);
        AiStatusSchema::assertEndpointGroup($this->endpointGroup);
    }

    public function toArray(): array
    {
        return [
            'feature_key' => $this->featureKey,
            'capability_group' => $this->capabilityGroup,
            'requires_ai' => $this->requiresAi,
            'has_manual_fallback' => $this->hasManualFallback,
            'fallback_ui_mode' => $this->fallbackUiMode,
            'degradation_message_key' => $this->degradationMessageKey,
            'operator_critical' => $this->operatorCritical,
            'endpoint_group' => $this->endpointGroup,
        ];
    }

    private function assertNonEmptyString(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw AiStatusContractException::invalidValue(sprintf('AI capability definition %s', $label));
        }
    }
}
