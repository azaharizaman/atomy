<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class ProviderAiProvenance
{
    /**
     * @param array<string, scalar|null> $reliabilityHints
     */
    public function __construct(
        public string $providerName,
        public AiEndpointGroup $endpointGroup,
        public string $modelRevision,
        public string $promptTemplateVersion,
        public string $requestTraceId,
        public string $inputHash,
        public string $outputHash,
        public int $latencyMs,
        public ?float $confidence,
        public array $reliabilityHints,
        public DateTimeImmutable $processedAt,
    ) {
        $this->assertNonEmpty($this->providerName, 'provider name');
        $this->assertNonEmpty($this->modelRevision, 'model revision');
        $this->assertNonEmpty($this->promptTemplateVersion, 'prompt template version');
        $this->assertNonEmpty($this->requestTraceId, 'request trace id');
        $this->assertNonEmpty($this->inputHash, 'input hash');
        $this->assertNonEmpty($this->outputHash, 'output hash');

        if ($this->latencyMs < 0) {
            throw ProcurementMlContractException::invalidValue('provider latency');
        }

        $this->assertConfidence($this->confidence);
        $this->assertScalarMap($this->reliabilityHints, 'provider reliability hints');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider_name' => $this->providerName,
            'endpoint_group' => $this->endpointGroup->value,
            'model_revision' => $this->modelRevision,
            'prompt_template_version' => $this->promptTemplateVersion,
            'request_trace_id' => $this->requestTraceId,
            'input_hash' => $this->inputHash,
            'output_hash' => $this->outputHash,
            'latency_ms' => $this->latencyMs,
            'confidence' => $this->confidence,
            'reliability_hints' => $this->reliabilityHints,
            'processed_at' => $this->processedAt->format(DATE_ATOM),
        ];
    }

    private function assertNonEmpty(string $value, string $subject): void
    {
        if (trim($value) === '') {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    private function assertConfidence(?float $confidence): void
    {
        if ($confidence !== null && ($confidence < 0.0 || $confidence > 1.0)) {
            throw ProcurementMlContractException::invalidValue('provider confidence');
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function assertScalarMap(array $values, string $subject): void
    {
        foreach ($values as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw ProcurementMlContractException::invalidValue(sprintf('%s: invalid key "%s"', $subject, (string) $key));
            }

            if (!is_scalar($value) && $value !== null) {
                throw ProcurementMlContractException::invalidValue(sprintf('%s: invalid value for key "%s"', $subject, (string) $key));
            }
        }
    }
}
