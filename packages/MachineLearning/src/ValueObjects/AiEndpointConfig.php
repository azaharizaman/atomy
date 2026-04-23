<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

final readonly class AiEndpointConfig
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        public AiEndpointGroup $endpointGroup,
        public string $providerName,
        public string $endpointUri,
        public int $timeoutSeconds = 30,
        public bool $enabled = true,
        public array $metadata = [],
    ) {
        $this->assertNonEmptyString($this->providerName, 'Provider name');
        $this->assertNonEmptyString($this->endpointUri, 'Endpoint URI');

        if ($this->timeoutSeconds < 1) {
            throw AiRuntimeContractException::invalidValue('AI endpoint timeout');
        }

        $this->assertScalarMetadata($this->metadata);
    }

    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup->value,
            'provider_name' => $this->providerName,
            'endpoint_uri' => $this->endpointUri,
            'timeout_seconds' => $this->timeoutSeconds,
            'enabled' => $this->enabled,
            'metadata' => $this->metadata,
        ];
    }

    private function assertNonEmptyString(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw AiRuntimeContractException::invalidValue(sprintf('AI endpoint %s', strtolower($label)));
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function assertScalarMetadata(array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw AiRuntimeContractException::invalidValue('AI endpoint metadata');
            }

            if (!is_scalar($value) && $value !== null) {
                throw AiRuntimeContractException::invalidValue('AI endpoint metadata');
            }
        }
    }
}
