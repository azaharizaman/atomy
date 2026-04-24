<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use Nexus\MachineLearning\Enums\AiCapabilityGroup;
use Nexus\MachineLearning\Enums\AiFallbackUiMode;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

final readonly class AiCapabilityDefinition
{
    public function __construct(
        public string $featureKey,
        public AiCapabilityGroup $capabilityGroup,
        public bool $requiresAi,
        public bool $hasManualFallback,
        public AiFallbackUiMode $fallbackUiMode,
        public string $degradationMessageKey,
        public bool $operatorCritical,
    ) {
        $this->assertNonEmptyString($this->featureKey, 'Feature key');
        $this->assertNonEmptyString($this->degradationMessageKey, 'Degradation message key');
    }

    public function toArray(): array
    {
        return [
            'feature_key' => $this->featureKey,
            'capability_group' => $this->capabilityGroup->value,
            'requires_ai' => $this->requiresAi,
            'has_manual_fallback' => $this->hasManualFallback,
            'fallback_ui_mode' => $this->fallbackUiMode->value,
            'degradation_message_key' => $this->degradationMessageKey,
            'operator_critical' => $this->operatorCritical,
        ];
    }

    private function assertNonEmptyString(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw AiRuntimeContractException::invalidValue(sprintf('AI capability definition %s', strtolower($label)));
        }
    }
}
