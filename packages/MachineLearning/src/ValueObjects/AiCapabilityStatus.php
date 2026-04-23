<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use Nexus\MachineLearning\Enums\AiFallbackUiMode;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

final readonly class AiCapabilityStatus
{
    /**
     * @param array{
     *     status: AiHealth|non-empty-string,
     *     available: bool,
     *     fallback_ui_mode: AiFallbackUiMode|non-empty-string,
     *     message_key: non-empty-string,
     *     reason_codes: list<non-empty-string>,
     *     operator_critical: bool
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $requiredKeys = ['status', 'available', 'fallback_ui_mode', 'message_key', 'reason_codes', 'operator_critical'];
        $missingKeys = array_values(array_diff($requiredKeys, array_keys($data)));

        if ($missingKeys !== []) {
            throw AiRuntimeContractException::missingFields('AI capability status', $missingKeys);
        }

        $status = $data['status'] instanceof AiHealth
            ? $data['status']
            : AiHealth::fromConfig((string) $data['status']);

        $fallbackUiMode = $data['fallback_ui_mode'] instanceof AiFallbackUiMode
            ? $data['fallback_ui_mode']
            : AiFallbackUiMode::fromConfig((string) $data['fallback_ui_mode']);

        $messageKey = trim((string) $data['message_key']);
        if ($messageKey === '') {
            throw AiRuntimeContractException::invalidValue('AI capability status message key');
        }

        if (!is_array($data['reason_codes'])) {
            throw AiRuntimeContractException::invalidValue('AI capability status reason codes');
        }

        $reasonCodes = [];
        foreach ($data['reason_codes'] as $reasonCode) {
            if (!is_string($reasonCode) || trim($reasonCode) === '') {
                throw AiRuntimeContractException::invalidValue('AI capability status reason codes');
            }

            $reasonCodes[] = $reasonCode;
        }

        if (!is_bool($data['available'])) {
            throw AiRuntimeContractException::invalidValue('AI capability status availability');
        }

        if (!is_bool($data['operator_critical'])) {
            throw AiRuntimeContractException::invalidValue('AI capability status criticality');
        }

        return new self(
            status: $status,
            available: $data['available'],
            fallbackUiMode: $fallbackUiMode,
            messageKey: $messageKey,
            reasonCodes: $reasonCodes,
            operatorCritical: $data['operator_critical'],
        );
    }

    /**
     * @param list<non-empty-string> $reasonCodes
     */
    public function __construct(
        public AiHealth $status,
        public bool $available,
        public AiFallbackUiMode $fallbackUiMode,
        public string $messageKey,
        public array $reasonCodes,
        public bool $operatorCritical,
    ) {
        if (trim($this->messageKey) === '') {
            throw AiRuntimeContractException::invalidValue('AI capability status message key');
        }

        foreach ($this->reasonCodes as $reasonCode) {
            if (!is_string($reasonCode) || trim($reasonCode) === '') {
                throw AiRuntimeContractException::invalidValue('AI capability status reason codes');
            }
        }
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'available' => $this->available,
            'fallback_ui_mode' => $this->fallbackUiMode->value,
            'message_key' => $this->messageKey,
            'reason_codes' => $this->reasonCodes,
            'operator_critical' => $this->operatorCritical,
        ];
    }
}
